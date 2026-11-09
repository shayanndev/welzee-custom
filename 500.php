<?php
// 500.php - standalone error page (no dependencies, safe to render when the app is broken).
$errorCode = isset($errorCode) ? (int)$errorCode : 500;
$errorMessage = isset($errorMessage) ? (string)$errorMessage : 'Something went wrong. Please try again later.';
if (!headers_sent()) {
    http_response_code($errorCode);
    header('Content-Type: text/html; charset=utf-8');
    header('X-Robots-Tag: noindex');
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Temporarily unavailable | Welzee</title>
    <style>
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f8fafc;font-family:Inter,Arial,sans-serif;color:#0f172a;text-align:center;padding:24px}
        .card{max-width:480px}
        h1{font-size:2rem;margin:0 0 12px}
        p{color:#64748b;line-height:1.6;margin:0 0 24px}
        a{display:inline-block;background:#22c55e;color:#fff;text-decoration:none;font-weight:700;padding:12px 28px;border-radius:999px}
    </style>
</head>
<body>
    <div class="card">
        <h1>We'll be right back</h1>
        <p><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
        <a href="./">Back to Home</a>
    </div>
</body>
</html>

