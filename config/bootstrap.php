<?php
/**
 * config/bootstrap.php
 *
 * Central, idempotent application bootstrap (config loading, error handling,
 * timezone, base URL, secure sessions, CSRF helpers).
 *
 * Secrets are NEVER stored in this file. They live in a private config file:
 *   1. $WELZEE_CONFIG_FILE environment variable (if set), else
 *   2. <parent of document root>/welzee_private/config.php
 *      (Namecheap: /home/welzywbv/welzee_private/config.php), else
 *   3. config/config.local.php  (local XAMPP development only; not deployed)
 *
 * Requires PHP >= 8.0.
 */

if (defined('WELZEE_BOOTSTRAPPED')) {
    return;
}
define('WELZEE_BOOTSTRAPPED', true);
define('APP_ROOT', dirname(__DIR__));

/* ------------------------------------------------------------------ */
/* Configuration                                                       */
/* ------------------------------------------------------------------ */

function app_config_defaults(): array
{
    return [
        'env'       => 'production',            // 'production' | 'local'
        'site_url'  => '',                      // e.g. https://welzee.com (no trailing slash needed)
        'timezone'  => 'Asia/Karachi',
        'log_dir'   => '',                      // e.g. /home/welzywbv/welzee_private/logs
        'db' => [
            'host' => 'localhost',
            'port' => 3306,
            'name' => '',
            'user' => '',
            'pass' => '',
            'time_zone' => '+05:00',            // Pakistan has no DST
        ],
        'smtp' => [
            'host'       => '',
            'port'       => 465,
            'secure'     => 'ssl',              // 'ssl' (465) | 'tls' (587) | '' (none)
            'user'       => '',
            'pass'       => '',
            'from_email' => '',
            'from_name'  => 'Welzee Custom Orders',
            'to_email'   => '',                 // who receives order notifications
        ],
        'limits' => [
            'orders_per_hour_per_ip' => 5,
            'min_seconds_between_orders' => 10,
        ],
    ];
}

function app_config(?string $path = null, $default = null)
{
    static $cfg = null;
    static $source = null;

    if ($cfg === null) {
        $cfg = app_config_defaults();
        $candidates = [];
        $env = getenv('WELZEE_CONFIG_FILE');
        if ($env) {
            $candidates[] = $env;
        }
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $candidates[] = dirname(rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/')) . '/welzee_private/config.php';
        }
        $candidates[] = __DIR__ . '/config.local.php';

        foreach ($candidates as $file) {
            if (is_file($file) && is_readable($file)) {
                $loaded = require $file;
                if (is_array($loaded)) {
                    $cfg = array_replace_recursive($cfg, $loaded);
                    $source = $file;
                    break;
                }
            }
        }
        $cfg['_source'] = $source;
    }

    if ($path === null) {
        return $cfg;
    }
    $node = $cfg;
    foreach (explode('.', $path) as $part) {
        if (!is_array($node) || !array_key_exists($part, $node)) {
            return $default;
        }
        $node = $node[$part];
    }
    return $node;
}

/* ------------------------------------------------------------------ */
/* Environment helpers                                                 */
/* ------------------------------------------------------------------ */

function app_is_local_host(): bool
{
    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    $host = preg_replace('/:\d+$/', '', $host);
    return $host === 'localhost' || $host === '127.0.0.1' || $host === '[::1]';
}

function app_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return true;
    }
    return strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

/**
 * Base URL with trailing slash. Production uses the configured site_url;
 * local development auto-detects the XAMPP sub-folder.
 */
function app_base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $configured = trim((string)app_config('site_url', ''));
    if ($configured !== '' && !app_is_local_host()) {
        return $base = rtrim($configured, '/') . '/';
    }
    $protocol = app_is_https() ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (app_is_local_host()) {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $segments = array_values(array_filter(explode('/', trim($scriptDir, '/'))));
        $folder = $segments[0] ?? '';
        // never treat our own sub-directories as the project folder
        if (in_array($folder, ['api', 'includes', 'config'], true)) {
            $folder = '';
        }
        return $base = $protocol . '://' . $host . ($folder !== '' ? '/' . $folder . '/' : '/');
    }
    // Production without site_url configured: fall back to the request host
    return $base = $protocol . '://' . preg_replace('/[^a-z0-9.\-:\[\]]/i', '', $host) . '/';
}

/* ------------------------------------------------------------------ */
/* Error handling                                                      */
/* ------------------------------------------------------------------ */

$__isLocal = app_config('env') === 'local' && app_is_local_host();
ini_set('display_errors', $__isLocal ? '1' : '0');
ini_set('display_startup_errors', $__isLocal ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
$__logDir = (string)app_config('log_dir', '');
if ($__logDir !== '' && is_dir($__logDir) && is_writable($__logDir)) {
    ini_set('error_log', rtrim($__logDir, '/\\') . '/php-error.log');
}
unset($__isLocal, $__logDir);

date_default_timezone_set((string)app_config('timezone', 'Asia/Karachi'));

/** Send a safe error response and stop. JSON for API requests, HTML otherwise. */
function app_fail(int $code = 500, string $message = 'Something went wrong. Please try again later.'): void
{
    if (!headers_sent()) {
        http_response_code($code);
        header('Cache-Control: no-store');
    }
    if (defined('APP_JSON')) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['status' => 'error', 'message' => $message]);
        exit;
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    $errorCode = $code;
    $errorMessage = $message;
    require APP_ROOT . '/500.php';
    exit;
}

set_exception_handler(function (Throwable $e): void {
    error_log('Uncaught ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    app_fail(500);
});

register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        error_log('Fatal: ' . $err['message'] . ' in ' . $err['file'] . ':' . $err['line']);
        if (!headers_sent()) {
            app_fail(500);
        }
    }
});

/* ------------------------------------------------------------------ */
/* Sessions + CSRF                                                     */
/* ------------------------------------------------------------------ */

function app_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('WELZEESESS');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => app_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function csrf_token(): string
{
    app_session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(?string $token): bool
{
    app_session_start();
    return is_string($token) && $token !== ''
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** HTML-escape helper. */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

