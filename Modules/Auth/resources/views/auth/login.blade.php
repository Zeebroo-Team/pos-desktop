<!doctype html>
<html lang="en" data-theme="night">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login</title>
    <style>
        :root{--bg:#090d1a;--card:#10172a;--text:#e5e7eb;--muted:#94a3b8;--primary:#7c3aed;--primary2:#2563eb;--border:#334155;--error:#ef4444}
        html[data-theme="light"]{--bg:#f3f4f6;--card:#fff;--text:#111827;--muted:#475569;--primary:#4f46e5;--primary2:#2563eb;--border:#d1d5db}
        html[data-theme="ocean"]{--bg:#082f49;--card:#0c4a6e;--text:#e0f2fe;--muted:#bae6fd;--primary:#06b6d4;--primary2:#0ea5e9;--border:#0369a1}
        *{box-sizing:border-box} body{margin:0;min-height:100vh;display:grid;place-items:center;background:radial-gradient(circle at 10% 10%,var(--primary2),var(--bg) 45%);font-family:Inter,system-ui,sans-serif;color:var(--text);padding:20px}
        .card{width:100%;max-width:450px;background:color-mix(in srgb,var(--card) 90%,transparent);border:1px solid var(--border);padding:30px;border-radius:18px;box-shadow:0 20px 44px rgba(0,0,0,.25);backdrop-filter:blur(10px)}
        h1{margin:0 0 8px;font-size:31px}.sub{margin:0 0 22px;color:var(--muted)}
        .field{margin-bottom:14px} label{display:block;font-size:14px;color:var(--muted);margin-bottom:6px}
        input,select{width:100%;padding:12px 13px;border-radius:12px;border:1px solid var(--border);background:transparent;color:var(--text);outline:none}
        input:focus,select:focus{border-color:var(--primary)}
        .error{color:var(--error);font-size:13px;min-height:18px;margin-top:5px}
        .row{display:flex;justify-content:space-between;align-items:center;margin:8px 0 15px}
        button{width:100%;border:0;border-radius:12px;padding:12px 14px;color:#fff;background:linear-gradient(135deg,var(--primary),var(--primary2));font-weight:600;cursor:pointer}
        .meta{margin-top:16px;text-align:center;color:var(--muted);font-size:14px}.meta a,.theme a{color:var(--text);text-decoration:none;font-weight:600}
        .theme{text-align:center;margin-top:8px;color:var(--muted);font-size:13px}
    </style>
</head>
<body>
<div class="card">
    <h1>Welcome back</h1>
    <p class="sub">Login to continue to your role-based dashboard.</p>
    <form method="post" action="{{ route('login.submit') }}">
        @csrf
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required>
            <div class="error">@error('email'){{ $message }}@enderror</div>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>
            <div class="error">@error('password'){{ $message }}@enderror</div>
        </div>
        <div class="row"><label><input style="width:auto" type="checkbox" name="remember"> Remember me</label></div>
        <button type="submit">Sign in</button>
    </form>
    <div class="meta">New here? <a href="{{ route('register.page') }}">Create account</a></div>
    <div class="theme">Theme: <a href="#" data-theme="night">Night</a> • <a href="#" data-theme="light">Light</a> • <a href="#" data-theme="ocean">Ocean</a></div>
</div>
<script>
    const root = document.documentElement;
    const savedTheme = localStorage.getItem("ui_theme");
    root.setAttribute("data-theme", savedTheme || "night");
    document.querySelectorAll(".theme a[data-theme]").forEach((link) => link.addEventListener("click", (e) => {
        e.preventDefault();
        root.setAttribute("data-theme", link.dataset.theme);
        localStorage.setItem("ui_theme", link.dataset.theme);
    }));
</script>
</body>
</html>
