<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel</title>
    <style>
        body{margin:0;background:#0b1220;color:#e5e7eb;font-family:Inter,system-ui,sans-serif}
        .wrap{max-width:900px;margin:40px auto;padding:0 20px}
        .card{background:#111827;border:1px solid #334155;border-radius:14px;padding:22px}
        a{color:#60a5fa;text-decoration:none}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Admin Panel</h1>
        <p>This page is protected by Spatie role middleware (`role:admin`).</p>
        <p><a href="{{ route('dashboard') }}">Back to dashboard</a></p>
    </div>
</div>
</body>
</html>
