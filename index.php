<?php
// BanglaHost default page - replace this file with your own.
if (isset($_GET['phpinfo'])) { phpinfo(); exit; }
$php  = PHP_VERSION;
$srv  = $_SERVER['SERVER_SOFTWARE'] ?? 'web server';
$host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
$root = __DIR__;
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>
<!doctype html>
<html lang='en'>
<head>
<meta charset='utf-8'>
<meta name='viewport' content='width=device-width, initial-scale=1'>
<title><?= $e($host) ?> - BanglaHost</title>
<style>
  :root { color-scheme: light dark; }
  * { box-sizing: border-box; }
  body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px;
         font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif; background:#0f172a; color:#e2e8f0; }
  .card { width:100%; max-width:640px; background:#111827; border:1px solid #1f2937; border-radius:18px;
          overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.45); }
  .hero { background:linear-gradient(135deg,#2563eb,#7c3aed); padding:38px 32px; }
  .badge { display:inline-block; font-size:12px; letter-spacing:.08em; text-transform:uppercase;
           background:rgba(255,255,255,.18); padding:4px 11px; border-radius:999px; }
  h1 { margin:14px 0 6px; font-size:30px; line-height:1.15; word-break:break-all; }
  .hero p { margin:0; opacity:.92; }
  .body { padding:26px 32px 30px; }
  dl { display:grid; grid-template-columns:auto 1fr; gap:10px 18px; margin:0; font-size:14px; }
  dt { color:#94a3b8; }
  dd { margin:0; font-family:ui-monospace,'Cascadia Code',Consolas,monospace; word-break:break-all; }
  .hint { margin-top:22px; padding:14px 16px; background:#0b1220; border:1px dashed #334155;
          border-radius:12px; font-size:13px; color:#cbd5e1; }
  .hint code { color:#93c5fd; }
  .foot { margin-top:20px; display:flex; justify-content:space-between; align-items:center;
          font-size:13px; color:#94a3b8; }
  a { color:#93c5fd; text-decoration:none; }
  a:hover { text-decoration:underline; }
</style>
</head>
<body>
  <div class='card'>
    <div class='hero'>
      <span class='badge'>It works</span>
      <h1><?= $e($host) ?></h1>
      <p>Your site is ready - served by BanglaHost.</p>
    </div>
    <div class='body'>
      <dl>
        <dt>PHP</dt><dd><?= $e($php) ?> &middot; <a href='?phpinfo=1'>phpinfo()</a></dd>
        <dt>Server</dt><dd><?= $e($srv) ?></dd>
        <dt>Folder</dt><dd><?= $e($root) ?></dd>
      </dl>
      <div class='hint'>Drop your files in the folder above - replace <code>index.php</code> to get started.</div>
      <div class='foot'>
        <span>Powered by <strong>BanglaHost</strong></span>
        <span>BanglaHost | Mohammad Sheikh Shahinur Rahman</span>
      </div>
    </div>
  </div>
</body>
</html>
