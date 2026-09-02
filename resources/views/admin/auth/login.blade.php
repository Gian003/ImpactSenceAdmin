<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ImpactSense — Command Center Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            background: #EDEDEB;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }

        .login-shell {
            display: flex;
            width: 860px;
            max-width: 96vw;
            min-height: 500px;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 8px 40px rgba(0,0,0,.18);
            border: 2px solid #bfc4cc;
        }

        /* ── Left panel ── */
        .left-panel {
            width: 42%;
            background: #7B1A2E;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 28px;
            gap: 20px;
            text-align: center;
        }
        .left-panel img { width: 100px; height: 100px; object-fit: contain; }
        .left-panel h2 {
            color: #fff;
            font-size: 1.1rem;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.4;
        }

        /* ── Right panel ── */
        .right-panel {
            flex: 1;
            background: #7B1A2E;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 36px;
            border-left: 1px solid rgba(255,255,255,.12);
        }

        .right-panel .brand-logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
            margin-bottom: 6px;
        }
        .right-panel .brand-name {
            color: #fff;
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .right-panel .brand-sub {
            color: rgba(255,255,255,.55);
            font-size: .78rem;
            margin-bottom: 28px;
        }

        .field-wrap {
            width: 100%;
            background: rgba(255,255,255,.10);
            border: 1px solid rgba(255,255,255,.22);
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 14px;
            margin-bottom: 12px;
        }
        .field-wrap svg { flex-shrink: 0; opacity: .55; }
        .field-wrap input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: #fff;
            font-size: .9rem;
            padding: 13px 0;
        }
        .field-wrap input::placeholder { color: rgba(255,255,255,.4); }

        /* Visible focus indicator on the whole pill, since the input itself
           has outline:none — keyboard users need some visible sign of where
           they are. */
        .field-wrap:focus-within {
            outline: 2px solid #F4C5D0;
            outline-offset: 2px;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 30px;
            background: #F4C5D0;
            color: #7B1A2E;
            font-weight: 800;
            font-size: .9rem;
            letter-spacing: .1em;
            text-transform: uppercase;
            cursor: pointer;
            margin-top: 8px;
            transition: background .15s;
        }
        .btn-login:hover { background: #eaa8b8; }

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            margin-bottom: 4px;
        }
        .remember-row label {
            color: rgba(255,255,255,.55);
            font-size: .78rem;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }
        .remember-row a {
            color: rgba(255,255,255,.55);
            font-size: .78rem;
            text-decoration: none;
        }
        .remember-row a:hover { color: #fff; }

        .alert-danger-maroon {
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,100,100,.4);
            color: #fca5a5;
            border-radius: 7px;
            padding: 9px 14px;
            font-size: .83rem;
            width: 100%;
            margin-bottom: 12px;
        }

        .divider {
            width: 1px;
            background: rgba(255,255,255,.15);
            align-self: stretch;
        }

        @media (max-width: 600px) {
            .left-panel { display: none; }
            .divider { display: none; }
        }
    </style>
</head>
<body>
    <div class="login-shell">

        {{-- Left: PNP branding --}}
        <div class="left-panel">
            <img src="{{ asset('images/pnp_logo.png') }}"
                 onerror="this.style.display='none'"
                 alt="PNP Logo">
            <h2>PNP Urdaneta<br>Command<br>Center</h2>
        </div>

        <div class="divider"></div>

        {{-- Right: login form --}}
        <div class="right-panel">
            <img src="{{ asset('images/pnp_urdaneta_logo.png') }}"
                 class="brand-logo"
                 onerror="this.style.display='none'"
                 alt="PNP Urdaneta">
            <div class="brand-name">PNP Urdaneta</div>
            <div class="brand-sub">Sign in your account</div>

            @if($errors->any())
                <div class="alert-danger-maroon">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}" style="width:100%;">
                @csrf

                <div class="field-wrap">
                    <svg width="16" height="16" fill="none" stroke="#fff" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <input type="email" name="email" value="{{ old('email') }}"
                           placeholder="Email Address" required autofocus>
                </div>

                <div class="field-wrap">
                    <svg width="16" height="16" fill="none" stroke="#fff" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <input type="password" name="password" placeholder="Password" required>
                </div>

                <div class="remember-row">
                    <label>
                        <input type="checkbox" name="remember" style="accent-color:#F4C5D0;">
                        Keep me Logged in
                    </label>
                    <a href="#">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-login">Log In ▶</button>
            </form>

            <p style="color:rgba(255,255,255,.35); font-size:.72rem; margin-top:20px; margin-bottom:0; text-align:center;">
                TOC / Investigation officer?
                <a href="{{ route('login') }}" style="color:rgba(255,255,255,.55); text-decoration:none;">Sign in here →</a>
            </p>
        </div>

    </div>
</body>
</html>
