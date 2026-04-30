<!doctype html>
<html lang="en" data-theme="night">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register</title>
    <style>
        :root{--bg:#0b1220;--card:#121a2b;--text:#e2e8f0;--muted:#94a3b8;--primary:#14b8a6;--primary2:#2563eb;--border:#334155;--error:#ef4444}
        html[data-theme="light"]{--bg:#f5f7fb;--card:#fff;--text:#111827;--muted:#475569;--primary:#0d9488;--primary2:#2563eb;--border:#d1d5db}
        html[data-theme="ocean"]{--bg:#082f49;--card:#0b4b72;--text:#e0f2fe;--muted:#bae6fd;--primary:#06b6d4;--primary2:#0ea5e9;--border:#0369a1}
        *{box-sizing:border-box} body{margin:0;min-height:100vh;display:grid;place-items:center;background:radial-gradient(circle at 85% 0%,var(--primary2),var(--bg) 42%);font-family:Inter,system-ui,sans-serif;color:var(--text);padding:20px}
        .card{width:100%;max-width:500px;background:color-mix(in srgb,var(--card) 90%,transparent);border:1px solid var(--border);padding:30px;border-radius:18px;box-shadow:0 20px 44px rgba(0,0,0,.25);backdrop-filter:blur(10px)}
        h1{margin:0 0 8px;font-size:30px}.sub{margin:0 0 22px;color:var(--muted)}
        .field{margin-bottom:14px} label{display:block;font-size:14px;color:var(--muted);margin-bottom:6px}
        input,select{width:100%;padding:12px 13px;border-radius:12px;border:1px solid var(--border);background:transparent;color:var(--text);outline:none}
        input:focus,select:focus{border-color:var(--primary)}
        .error{color:var(--error);font-size:13px;min-height:18px;margin-top:5px}
        button{width:100%;border:0;border-radius:12px;padding:12px 14px;color:#fff;background:linear-gradient(135deg,var(--primary),var(--primary2));font-weight:600;cursor:pointer}
        .meta{margin-top:16px;text-align:center;color:var(--muted);font-size:14px}.meta a,.theme a{color:var(--text);text-decoration:none;font-weight:600}
        .theme{text-align:center;margin-top:8px;color:var(--muted);font-size:13px}
    </style>
</head>
<body>
<div class="card">
    <h1>Create your account</h1>
    <p class="sub">Register and choose your role to continue.</p>
    <form method="post" action="{{ route('register.submit') }}">
        @csrf
        <div class="field">
            <label for="name">Full name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required>
            <div class="error">@error('name'){{ $message }}@enderror</div>
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required>
            <div class="error">@error('email'){{ $message }}@enderror</div>
        </div>
        <div class="field">
            <label for="role">Role</label>
            <select id="role" name="role" required>
                <option value="user" @selected(old('role') === 'user')>User</option>
                <option value="admin" @selected(old('role') === 'admin')>Admin</option>
            </select>
            <div class="error">@error('role'){{ $message }}@enderror</div>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>
            <div class="error">@error('password'){{ $message }}@enderror</div>
        </div>
        <div class="field">
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required>
            <div class="error">@error('password_confirmation'){{ $message }}@enderror</div>
        </div>
        <button type="submit">Create account</button>
    </form>
    <div class="meta">Already have account? <a href="{{ route('login.page') }}">Sign in</a></div>
    <div class="theme">Theme: <a href="#" data-theme="night">Night</a> • <a href="#" data-theme="light">Light</a> • <a href="#" data-theme="ocean">Ocean</a></div>
</div>
<script>
    const root = document.documentElement;
    root.setAttribute("data-theme", localStorage.getItem("ui_theme") || "night");
    document.querySelectorAll(".theme a[data-theme]").forEach((link) => link.addEventListener("click", (e) => {
        e.preventDefault();
        root.setAttribute("data-theme", link.dataset.theme);
        localStorage.setItem("ui_theme", link.dataset.theme);
    }));
</script>
</body>
</html>
