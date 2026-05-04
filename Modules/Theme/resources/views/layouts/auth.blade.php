<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('Sign in') }} · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">
    <style>
        :root{
            --page:#e5e5e5;
            --card:#ffffff;
            --text:#0a0a0a;
            --muted:#525252;
            --border:#d4d4d4;
            --input-bg:#ffffff;
            --btn:#000000;
            --btn-text:#ffffff;
            --btn-hover:#facc15;
            --btn-hover-text:#0a0a0a;
            --error:#dc2626;
            --focus:#000000;
        }
        *{box-sizing:border-box}
        body{
            margin:0;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;
            padding:clamp(16px,4vmin,32px);font-family:Inter,system-ui,-apple-system,Segoe UI,sans-serif;
            color:var(--text);background:var(--page);
        }
        .auth-shell{width:100%;max-width:440px;position:relative;z-index:1;}
        .auth-card{
            width:100%;border-radius:16px;border:1px solid var(--border);
            background:var(--card);
            box-shadow:0 1px 3px rgba(0,0,0,.08),0 8px 24px rgba(0,0,0,.06);
            padding:clamp(28px,4vmin,36px) clamp(22px,3.5vmin,32px);
        }
        .auth-brand{display:flex;align-items:center;gap:14px;margin-bottom:clamp(20px,3vmin,28px);}
        .auth-brand__mark{
            width:48px;height:48px;border-radius:12px;display:grid;place-items:center;font-size:22px;
            color:var(--btn-text);background:var(--btn);flex-shrink:0;
        }
        .auth-brand__text h1{margin:0;font-size:clamp(1.35rem,2.8vw,1.55rem);font-weight:800;letter-spacing:-.03em;line-height:1.2;color:var(--text);}
        .auth-brand__text p{margin:5px 0 0;font-size:13px;line-height:1.45;color:var(--muted);font-weight:500;}
        .auth-body .sub{margin:0 0 22px;font-size:14px;line-height:1.5;color:var(--muted);}
        .field{margin-bottom:16px;}
        .field label{display:block;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--muted);margin-bottom:7px;}
        .field input,.field select{
            width:100%;padding:12px 14px;border-radius:10px;border:1px solid var(--border);background:var(--input-bg);
            color:var(--text);font-size:15px;outline:none;transition:border-color .15s ease,box-shadow .15s ease;
        }
        .field input::placeholder{color:#a3a3a3;}
        .field input:focus,.field select:focus{
            border-color:var(--focus);
            box-shadow:0 0 0 2px #ffffff,0 0 0 4px var(--focus);
        }
        .field .error{color:var(--error);font-size:13px;min-height:20px;margin-top:6px;}
        .auth-check{display:flex;align-items:center;gap:10px;margin:12px 0 18px;font-size:14px;color:var(--muted);}
        .auth-check input[type=checkbox]{width:18px;height:18px;accent-color:var(--btn);cursor:pointer;}
        .auth-check label{cursor:pointer;user-select:none;color:var(--text);}
        .auth-btn{
            width:100%;border:2px solid var(--btn);border-radius:10px;padding:13px 16px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;
            color:var(--btn-text);background:var(--btn);
            transition:background-color .15s ease,color .15s ease,border-color .15s ease;
        }
        .auth-btn:hover{background:var(--btn-hover);color:var(--btn-hover-text);border-color:var(--btn-hover);}
        .auth-btn:active{background:#eab308;color:var(--btn-hover-text);border-color:#eab308;}
        .auth-meta{margin-top:22px;text-align:center;font-size:14px;color:var(--muted);}
        .auth-meta a{color:var(--text);font-weight:700;text-decoration:underline;text-underline-offset:3px;}
        .auth-meta a:hover{color:var(--muted);}
        .auth-divider{display:flex;align-items:center;gap:12px;margin:22px 0 16px;color:var(--muted);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;}
        .auth-divider::before,.auth-divider::after{content:'';flex:1;height:1px;background:var(--border);}
        .auth-oauth{
            display:flex;align-items:center;justify-content:center;gap:10px;width:100%;
            padding:12px 16px;border-radius:10px;border:2px solid var(--border);
            background:var(--card);color:var(--text);font-size:15px;font-weight:700;font-family:inherit;text-decoration:none;
            transition:background-color .15s ease,border-color .15s ease,color .15s ease;
        }
        .auth-oauth:hover{background:var(--btn-hover);border-color:var(--btn-hover);color:var(--btn-hover-text);}
        .auth-oauth i{font-size:18px;}
    </style>
    @stack('auth-styles')
</head>
<body>
    <div class="auth-shell">
        <div class="auth-card">
            @yield('content')
        </div>
    </div>
    @stack('auth-scripts')
</body>
</html>
