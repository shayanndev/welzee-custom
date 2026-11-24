<?php
// api/submit_order_3d.php
// 3D customizer enquiry endpoint (front + back printing).
// Same protections as api/submit_order.php (which stays untouched):
// CSRF, same-origin, request size limit, per-IP rate limit, whitelist validation,
// content-based image validation (finfo + getimagesize), SVG safety check, random
// file names, prepared statements, transaction with rollback + file cleanup, and a
// notification email that never exposes mail errors.
//
// Extra input (on top of the old JSON):
//   logos:      [{ side: "front"|"back"|"rear", name, data }]   max 6, each max 5 MB
//   printSides: ["front","back"]
// Storage needs no database change: the first FRONT logo goes into logo_image_path
// (as today); every other logo is saved in uploads/logos and listed in the enquiry
// message text and in the notification email.

define('APP_JSON', true);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';

require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

// Whole JSON body: design JPEG (~1 MB) + several logos (base64 adds ~33%).
// Must stay below the server's post_max_size (checked at runtime below) and match
// API3D_MAX_REQUEST_BYTES in assets/js/3d/api-3d.js.
const MAX_REQUEST_BYTES = 30 * 1024 * 1024;
const MAX_DESIGN_BYTES  = 6 * 1024 * 1024;    // decoded design image
const MAX_LOGO_BYTES    = 5 * 1024 * 1024;    // decoded logo (matches client-side 5MB limit)
const MAX_LOGOS         = 6;
const ALLOWED_PRODUCTS  = ['tshirt', 'polo', 'hoodie', 'varsity', 'cap', 'visor', 'bag'];
const ALLOWED_SIZES     = ['S', 'M', 'L', 'XL', 'XXL'];
const ALLOWED_METHODS   = ['embroidery', 'print'];
const ALLOWED_LOGO_EXT  = ['jpg', 'png', 'gif', 'webp', 'svg'];
// Print sides per product (must match printZones in assets/js/3d/models.manifest.json)
const PRODUCT_SIDES     = [
    'cap'     => ['front', 'rear'],
    'visor'   => ['front'],
    '_default'=> ['front', 'back'],
];

function json_out(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body);
    exit;
}

function clean_line(string $s, int $max): string
{
    $s = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $s) ?? '';
    return trim(mb_substr($s, 0, $max, 'UTF-8'));
}

function clean_text(string $s, int $max): string
{
    $s = preg_replace('/[^\P{C}\n\r\t]+/u', '', $s) ?? '';
    return trim(mb_substr($s, 0, $max, 'UTF-8'));
}

/** php.ini size string ("40M") to bytes; 0 = unlimited/unknown. */
function ini_size_bytes(string $v): int
{
    $v = trim($v);
    if ($v === '') {
        return 0;
    }
    $n = (int)$v;
    switch (strtolower(substr($v, -1))) {
        case 'g': $n *= 1024;
        // no break
        case 'm': $n *= 1024;
        // no break
        case 'k': $n *= 1024;
    }
    return $n;
}

function allowed_sides(string $product): array
{
    return PRODUCT_SIDES[$product] ?? PRODUCT_SIDES['_default'];
}

/** Per-IP throttle (file based, fails open). Returns an error message or null. */
function rate_limit_check(string $ip, int $perHour, int $minGap): ?string
{
    $dir = sys_get_temp_dir() . '/welzee_rl';
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        return null;
    }
    $fh = @fopen($dir . '/' . hash('sha256', $ip), 'c+');
    if (!$fh) {
        return null;
    }
    flock($fh, LOCK_EX);
    $times = json_decode((string)stream_get_contents($fh), true);
    $now = time();
    $times = is_array($times) ? array_values(array_filter($times, fn($t) => is_int($t) && $t > $now - 3600)) : [];
    $msg = null;
    if ($times && ($now - end($times)) < $minGap) {
        $msg = 'Please wait a few seconds before submitting again.';
    } elseif (count($times) >= $perHour) {
        $msg = 'Too many requests. Please try again later or contact us directly.';
    } else {
        $times[] = $now;
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($times));
    }
    flock($fh, LOCK_UN);
    fclose($fh);
    return $msg;
}

/** Heuristic SVG safety check (SVG is also served with a sandboxing CSP, see uploads/.htaccess). */
function svg_is_safe(string $svg): bool
{
    if (stripos($svg, '<svg') === false || strlen($svg) > MAX_LOGO_BYTES) {
        return false;
    }
    $bad = '/<\s*(script|foreignObject|iframe|embed|object|audio|video|link|meta|style\b[^>]*@import)|\bon[a-z]+\s*=|javascript:|vbscript:|data:text\/html|<!ENTITY|<!DOCTYPE[^>]*\[|(xlink:)?href\s*=\s*["\']\s*(https?:)?\/\//i';
    return !preg_match($bad, $svg);
}

/**
 * Decode and validate a data: URL image. Returns [binary, extension].
 * The extension is derived from the DETECTED content, never from client input.
 */
function decode_image(string $dataUrl, array $allowedExt, int $maxBytes): array
{
    if (!preg_match('#^data:(image/[a-z0-9.+-]{1,30});base64,#i', substr($dataUrl, 0, 120), $m)) {
        throw new InvalidArgumentException('Invalid image data.');
    }
    $payload = str_replace(["\r", "\n", ' '], '', substr($dataUrl, strlen($m[0])));
    if ($payload === '' || strlen($payload) > (int)ceil($maxBytes * 4 / 3) + 8) {
        throw new InvalidArgumentException('Image is too large.');
    }
    $bin = base64_decode($payload, true);
    if ($bin === false || $bin === '' || strlen($bin) > $maxBytes) {
        throw new InvalidArgumentException('Invalid or oversized image.');
    }

    $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bin);

    if (isset($map[$mime])) {
        $ext = $map[$mime];
        $info = @getimagesizefromstring($bin);
        if ($info === false || $info[0] < 1 || $info[1] < 1 || $info[0] > 12000 || $info[1] > 12000) {
            throw new InvalidArgumentException('Invalid image.');
        }
    } elseif (strtolower($m[1]) === 'image/svg+xml' && in_array($mime, ['image/svg+xml', 'text/xml', 'text/plain', 'application/xml'], true) && svg_is_safe($bin)) {
        $ext = 'svg';
    } else {
        throw new InvalidArgumentException('Unsupported image type.');
    }
    if (!in_array($ext, $allowedExt, true)) {
        throw new InvalidArgumentException('Unsupported image type.');
    }
    return [$bin, $ext];
}

function store_upload(string $bin, string $ext, string $subdir, string $prefix): string
{
    $dir = APP_ROOT . '/uploads/' . $subdir;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Upload directory is not available: ' . $dir);
    }
    $name = $prefix . bin2hex(random_bytes(16)) . '.' . $ext;
    $path = $dir . '/' . $name;
    if (file_put_contents($path, $bin, LOCK_EX) === false) {
        throw new RuntimeException('Cannot write upload to ' . $path);
    }
    @chmod($path, 0644);
    return 'uploads/' . $subdir . '/' . $name;   // path stored in DB (unchanged format)
}

/* ------------------------- Request gate ------------------------- */

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    json_out(405, ['status' => 'error', 'message' => 'Method not allowed.']);
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
    $oh = strtolower((string)parse_url($origin, PHP_URL_HOST));
    $hh = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    if ($oh === '' || $oh !== $hh) {
        json_out(403, ['status' => 'error', 'message' => 'Cross-site requests are not allowed.']);
    }
}

if (!csrf_verify($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    json_out(403, ['status' => 'error', 'message' => 'Your session expired. Please refresh the page and try again.']);
}

$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
$postMax = ini_size_bytes((string)ini_get('post_max_size'));
if ($contentLength > MAX_REQUEST_BYTES || ($postMax > 0 && $contentLength > $postMax)) {
    json_out(413, ['status' => 'error', 'message' => 'The uploaded files are too large. Please use smaller logo files.']);
}
$raw = file_get_contents('php://input', false, null, 0, MAX_REQUEST_BYTES + 1);
if ($raw === false || strlen($raw) > MAX_REQUEST_BYTES) {
    json_out(413, ['status' => 'error', 'message' => 'The uploaded files are too large. Please use smaller logo files.']);
}
$input = json_decode($raw, true);
unset($raw);
if (!is_array($input)) {
    json_out(400, ['status' => 'error', 'message' => 'Invalid input data']);
}

/* ------------------------- Validation ------------------------- */

$product      = is_string($input['product'] ?? null) ? $input['product'] : '';
$color        = is_string($input['color'] ?? null) ? $input['color'] : '';
$designMethod = is_string($input['designMethod'] ?? null) ? $input['designMethod'] : 'embroidery';
$contact      = is_array($input['contact'] ?? null) ? $input['contact'] : [];
$qtyIn        = is_array($input['quantities'] ?? null) ? $input['quantities'] : [];
$designData   = is_string($input['designBase64'] ?? null) ? $input['designBase64'] : '';
$logosIn      = is_array($input['logos'] ?? null) ? $input['logos'] : [];
$sidesIn      = is_array($input['printSides'] ?? null) ? $input['printSides'] : [];

$name  = clean_line((string)($contact['name'] ?? ''), 100);
$email = clean_line((string)($contact['email'] ?? ''), 254);
$phone = clean_line((string)($contact['phone'] ?? ''), 30);
$notes = clean_text((string)($contact['notes'] ?? ''), 2000);

if ($name === '' || $email === '') {
    json_out(422, ['status' => 'error', 'message' => 'Name and Email are required.']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_out(422, ['status' => 'error', 'message' => 'Invalid email format.']);
}
if ($phone !== '' && !preg_match('/^[0-9+\-\s().]{3,30}$/', $phone)) {
    json_out(422, ['status' => 'error', 'message' => 'Invalid phone number.']);
}
if (!in_array($product, ALLOWED_PRODUCTS, true)) {
    json_out(422, ['status' => 'error', 'message' => 'Invalid product selection.']);
}
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
    json_out(422, ['status' => 'error', 'message' => 'Invalid colour selection.']);
}
if (!in_array($designMethod, ALLOWED_METHODS, true)) {
    json_out(422, ['status' => 'error', 'message' => 'Invalid design method.']);
}

$quantities = [];
foreach ($qtyIn as $size => $qty) {
    $size = (string)$size;
    if (!in_array($size, ALLOWED_SIZES, true) || !is_numeric($qty)) {
        json_out(422, ['status' => 'error', 'message' => 'Invalid size or quantity.']);
    }
    $qty = (int)$qty;
    if ($qty < 0 || $qty > 999) {
        json_out(422, ['status' => 'error', 'message' => 'Invalid size or quantity.']);
    }
    if ($qty > 0) {
        $quantities[$size] = $qty;
    }
}
if (!$quantities) {
    json_out(422, ['status' => 'error', 'message' => 'Please select at least one quantity.']);
}
$totalQuantity = array_sum($quantities);   // derived server-side, not trusted from the client

// Print sides (whitelist per product)
$allowedSides = allowed_sides($product);
if (count($sidesIn) > count($allowedSides)) {
    json_out(422, ['status' => 'error', 'message' => 'Invalid print side.']);
}
$sideSet = [];
foreach ($sidesIn as $s) {
    if (!is_string($s) || !in_array($s, $allowedSides, true)) {
        json_out(422, ['status' => 'error', 'message' => 'Invalid print side.']);
    }
    $sideSet[$s] = true;
}
if (count($logosIn) > MAX_LOGOS) {
    json_out(422, ['status' => 'error', 'message' => 'Too many logos. Please upload at most ' . MAX_LOGOS . ' logos per order.']);
}

try {
    $designBin = null;
    $designExt = null;
    if ($designData !== '') {
        [$designBin, $designExt] = decode_image($designData, ['jpg', 'png'], MAX_DESIGN_BYTES);
    }
    unset($designData);

    // Every logo: side whitelist + content-based validation
    $logos = [];
    foreach ($logosIn as $lg) {
        if (!is_array($lg)) {
            throw new InvalidArgumentException('Invalid logo data.');
        }
        // Older clients send no side (-> front); a side that is present must be a whitelisted string
        $side = array_key_exists('side', $lg) ? $lg['side'] : 'front';
        if (!is_string($side) || !in_array($side, $allowedSides, true)) {
            throw new InvalidArgumentException('Invalid print side.');
        }
        $logoData = is_string($lg['data'] ?? null) ? $lg['data'] : '';
        if ($logoData === '') {
            continue;
        }
        [$bin, $ext] = decode_image($logoData, ALLOWED_LOGO_EXT, MAX_LOGO_BYTES);
        $logos[] = [
            'side' => $side,
            'name' => clean_line(is_string($lg['name'] ?? null) ? $lg['name'] : 'logo', 120),
            'bin'  => $bin,
            'ext'  => $ext,
        ];
        $sideSet[$side] = true;
    }
    unset($logosIn, $input);
} catch (InvalidArgumentException $e) {
    json_out(422, ['status' => 'error', 'message' => $e->getMessage()]);
}

// Decorated sides in the product's natural order
$printSides = array_values(array_filter($allowedSides, fn($s) => isset($sideSet[$s])));

$limits = app_config('limits');
$throttle = rate_limit_check($_SERVER['REMOTE_ADDR'] ?? 'unknown', (int)$limits['orders_per_hour_per_ip'], (int)$limits['min_seconds_between_orders']);
if ($throttle !== null) {
    json_out(429, ['status' => 'error', 'message' => $throttle]);
}

/* ------------------------- Persist ------------------------- */

app_session_start();
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

$savedFiles = [];
$logoPath = '';          // first FRONT logo -> logo_image_path (unchanged column)
$extraLogos = [];        // every other logo -> message text + email
try {
    $designPath = '';
    if ($designBin !== null) {
        $designPath = store_upload($designBin, $designExt, 'designs', 'design_');
        $savedFiles[] = APP_ROOT . '/' . $designPath;
    }

    $perSide = [];
    foreach ($logos as $lg) {
        $path = store_upload($lg['bin'], $lg['ext'], 'logos', 'logo_');
        $savedFiles[] = APP_ROOT . '/' . $path;
        if ($logoPath === '' && $lg['side'] === 'front') {
            $logoPath = $path;
            continue;
        }
        $perSide[$lg['side']] = ($perSide[$lg['side']] ?? 0) + 1;
        $n = $perSide[$lg['side']] + ($lg['side'] === 'front' ? 1 : 0);   // front #1 is the main logo
        $extraLogos[] = [
            'label' => ucfirst($lg['side']) . ' logo' . ($n > 1 ? ' ' . $n : ''),
            'path'  => $path,
            'name'  => $lg['name'],
        ];
    }

    // Summary appended to the customer's notes (no database change needed)
    $summary = '[3D customizer] Print sides: ' . ($printSides ? implode(', ', $printSides) : 'none') . '.';
    foreach ($extraLogos as $x) {
        $summary .= ' ' . $x['label'] . ': ' . $x['path'] . ($x['name'] !== '' ? ' (' . $x['name'] . ')' : '') . '.';
    }
    $message = $notes === '' ? $summary : $notes . "\n\n" . $summary;

    $conn->begin_transaction();
    $stmt = $conn->prepare("INSERT INTO enquiries (user_id, name, email, phone, message, product_type, base_color, design_method, total_quantity, design_image_path, logo_image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssssiss", $user_id, $name, $email, $phone, $message, $product, $color, $designMethod, $totalQuantity, $designPath, $logoPath);
    $stmt->execute();
    $enquiry_id = (int)$stmt->insert_id;
    $stmt->close();

    $stmtItems = $conn->prepare("INSERT INTO enquiry_items (enquiry_id, size, quantity) VALUES (?, ?, ?)");
    foreach ($quantities as $size => $qty) {
        $stmtItems->bind_param("isi", $enquiry_id, $size, $qty);
        $stmtItems->execute();
    }
    $stmtItems->close();
    $conn->commit();
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $ignored) {}
    foreach ($savedFiles as $f) { @unlink($f); }
    error_log('3D Order Submission Error: ' . $e->getMessage());
    json_out(500, ['status' => 'error', 'message' => 'A server error occurred while saving the order.']);
}

/* ------------------------- Notification email ------------------------- */

$smtp = app_config('smtp');
$smtpReady = true;
foreach (['host', 'user', 'pass', 'to_email'] as $k) {
    $v = (string)($smtp[$k] ?? '');
    if ($v === '' || stripos($v, 'TO_BE_CONFIGURED') !== false) {
        $smtpReady = false;
    }
}

// Local development safety: config.local.php may hold real SMTP settings, so a local
// test order must never send a real email. ALL three conditions come from the server,
// never from request headers the visitor controls:
//   1. the active config file is config/config.local.php (git-ignored, never deployed),
//   2. that config says env = 'local' (default and live config are 'production'),
//   3. the TCP peer address is loopback (REMOTE_ADDR is set by the web server).
// On the live server conditions 1 and 2 are false, so the email is always sent
// exactly as in api/submit_order.php.
$localDev = basename((string)app_config('_source')) === 'config.local.php'
    && app_config('env') === 'local'
    && in_array((string)($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1', '::1'], true);

if (!$smtpReady) {
    error_log("Welzee: SMTP not configured - 3D enquiry #{$enquiry_id} saved but NO notification email was sent.");
} elseif ($localDev) {
    error_log("Welzee: local development - 3D enquiry #{$enquiry_id} saved, notification email skipped.");
} else {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = (string)$smtp['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = (string)$smtp['user'];
        $mail->Password   = (string)$smtp['pass'];
        $secure           = strtolower((string)($smtp['secure'] ?? 'ssl'));
        $mail->SMTPSecure = $secure === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : ($secure === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : '');
        $mail->SMTPAutoTLS = $secure !== '';
        $mail->Port       = (int)($smtp['port'] ?? 465);
        $mail->Timeout    = 15;
        $mail->CharSet    = 'UTF-8';

        $fromEmail = (string)($smtp['from_email'] ?: $smtp['user']);
        $mail->setFrom($fromEmail, (string)($smtp['from_name'] ?: 'Welzee Custom Orders'));
        $mail->addAddress((string)$smtp['to_email']);
        $mail->addReplyTo($email, $name);

        $mail->isHTML(true);
        $mail->Subject = 'New 3D Custom Order Request: ' . ucfirst($product) . ' from ' . $name;

        $base = app_base_url();
        $body  = '<h2>New 3D Custom Order Details</h2>';
        $body .= '<p><strong>Enquiry ID:</strong> #' . $enquiry_id . '</p>';
        $body .= '<p><strong>Name:</strong> ' . e($name) . '</p>';
        $body .= '<p><strong>Email:</strong> <a href="mailto:' . e($email) . '">' . e($email) . '</a></p>';
        $body .= '<p><strong>Phone:</strong> ' . e($phone) . '</p><hr>';
        $body .= '<p><strong>Product:</strong> ' . e(ucfirst($product)) . '</p>';
        $body .= '<p><strong>Color Hex:</strong> ' . e($color) . '</p>';
        $body .= '<p><strong>Design Method:</strong> ' . e(ucfirst($designMethod)) . '</p>';
        $body .= '<p><strong>Print Sides:</strong> ' . e($printSides ? implode(', ', array_map('ucfirst', $printSides)) : 'None') . '</p>';
        $body .= '<p><strong>Total Quantity:</strong> ' . (int)$totalQuantity . '</p>';
        $body .= '<h3>Size Breakdown:</h3><ul>';
        foreach ($quantities as $size => $qty) {
            $body .= '<li>' . e($size) . ': ' . (int)$qty . '</li>';
        }
        $body .= '</ul><h3>Notes:</h3><p>' . nl2br(e($notes)) . '</p><hr>';
        if ($designPath !== '') {
            $body .= '<p><strong>Design Mockup Link (front/back views):</strong> <a href="' . e($base . $designPath) . '">View Mockup</a></p>';
        }
        if ($logoPath !== '') {
            $body .= '<p><strong>Front Logo Link:</strong> <a href="' . e($base . $logoPath) . '">Download Logo</a></p>';
        }
        if ($extraLogos) {
            $body .= '<h3>Additional Logos:</h3><ul>';
            foreach ($extraLogos as $x) {
                $body .= '<li><strong>' . e($x['label']) . ':</strong> <a href="' . e($base . $x['path']) . '">Download</a>'
                       . ($x['name'] !== '' ? ' (' . e($x['name']) . ')' : '') . '</li>';
            }
            $body .= '</ul>';
        }

        $mail->Body    = $body;
        $mail->AltBody = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br />', '</li>', '</p>', '<hr>'], ["\n", "\n", "\n", "\n\n", "\n---\n"], $body)), ENT_QUOTES, 'UTF-8'));
        $mail->send();
    } catch (Throwable $e) {
        // Order is already saved; never expose mail errors to the visitor.
        error_log('PHPMailer Error (3D enquiry #' . $enquiry_id . '): ' . $e->getMessage());
    }
}

json_out(200, ['status' => 'success', 'message' => 'Order submitted successfully', 'id' => $enquiry_id]);
