<!doctype html>
<html lang="en" data-theme="night">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard</title>
    <style>
        :root{--bg:#0f172a;--card:#111827;--text:#e5e7eb;--muted:#9ca3af;--border:#334155;--primary:#7c3aed}
        html[data-theme="light"]{--bg:#f3f4f6;--card:#fff;--text:#111827;--muted:#4b5563;--border:#d1d5db;--primary:#2563eb}
        html[data-theme="ocean"]{--bg:#082f49;--card:#0c4a6e;--text:#e0f2fe;--muted:#bae6fd;--border:#0369a1;--primary:#06b6d4}
        body{margin:0;background:var(--bg);color:var(--text);font-family:Inter,system-ui,sans-serif}
        .layout{display:grid;grid-template-columns:260px 1fr;min-height:100vh}
        .sidebar{background:var(--card);border-right:1px solid var(--border);padding:24px 18px;position:sticky;top:0;height:100vh}
        .brand{font-weight:700;font-size:20px;margin-bottom:22px}
        .menu{display:flex;flex-direction:column;gap:8px}
        .menu a{display:block;padding:10px 12px;border:1px solid transparent;border-radius:10px;text-decoration:none;color:var(--text)}
        .menu a.active,.menu a:hover{border-color:var(--border);background:color-mix(in srgb,var(--primary) 14%,transparent)}
        .content{padding:28px}
        .card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:24px;max-width:920px}
        .top{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start}
        h1{margin:0 0 4px}
        .muted{color:var(--muted)}
        .chip{display:inline-block;border:1px solid var(--border);padding:6px 12px;border-radius:999px;margin:8px 8px 0 0}
        button,.linkbtn{border:0;border-radius:10px;padding:10px 14px;background:var(--primary);color:#fff;cursor:pointer;text-decoration:none;display:inline-block}
        @media (max-width: 900px){.layout{grid-template-columns:1fr}.sidebar{position:static;height:auto;border-right:0;border-bottom:1px solid var(--border)}}
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">SociBiz Panel</div>
        <nav class="menu">
            <a href="{{ route('dashboard') }}" class="active">Dashboard</a>
            @if(auth()->user()->hasRole('admin'))
                <a href="{{ route('admin.panel') }}">Admin Panel</a>
            @endif
        </nav>
        <form method="post" action="{{ route('logout') }}" style="margin-top:20px;">
            @csrf
            <button type="submit" style="width:100%;">Logout</button>
        </form>
    </aside>
    <main class="content">
        <div class="card">
            <div class="top">
                <div>
                    <h1>Hello, {{ auth()->user()->name }}</h1>
                    <div class="muted">{{ auth()->user()->email }}</div>
                    <div class="muted" style="margin-top:8px;">Roles:</div>
                    @foreach(auth()->user()->getRoleNames() as $role)
                        <span class="chip">{{ $role }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
