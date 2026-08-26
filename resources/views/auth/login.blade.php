<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ImpactSense — Officer Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Genos:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/auth/login.css') }}" rel="stylesheet">
</head>
<body>
    <div class="login-shell">

        {{-- Left: PNP branding --}}
        <div class="left-panel">
            <img src="{{ asset('images/pnp_logo.png') }}"
                 onerror="this.style.display='none'"
                 alt="PNP Logo">
            <h2>PNP<br>Urdaneta<br>City</h2>
        </div>

        <div class="divider"></div>

        {{-- Right: login form --}}
        <div class="right-panel">
            <img src="{{ asset('images/pnp_urdaneta_logo.png') }}"
                 class="brand-logo"
                 onerror="this.style.display='none'"
                 alt="PNP Urdaneta">
            <div class="brand-name">ImpactSense</div>
            <div class="brand-sub">Officer sign in</div>

            @if(session('status'))
                <div class="alert-danger-maroon" style="border-color:rgba(100,255,100,.3); color:#a7f3d0;">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert-danger-maroon">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" style="width:100%;">
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
                    <input type="password" name="password" id="password"
                           placeholder="Password" required>
                    <button type="button" onclick="toggleVis()"
                            style="background:none; border:none; padding:0; cursor:pointer; opacity:.55;" id="eyeBtn">
                        <svg id="eyeIcon" width="16" height="16" fill="none" stroke="#fff" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>

                <div class="remember-row">
                    <label>
                        <input type="checkbox" name="remember" style="accent-color:#F4C5D0;">
                        Keep me Logged in
                    </label>
                    <a href="{{ route('password.request') }}">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-login">Log In ▶</button>
            </form>

            <p style="color:rgba(255,255,255,.35); font-size:.72rem; margin-top:20px; margin-bottom:0; text-align:center;">
                Command Center admin?
                <a href="{{ route('admin.login') }}" style="color:rgba(255,255,255,.55); text-decoration:none;">Sign in here →</a>
            </p>
        </div>

    </div>

    <script>
    function toggleVis() {
        const inp = document.getElementById('password');
        inp.type = inp.type === 'password' ? 'text' : 'password';
        document.getElementById('eyeBtn').style.opacity = inp.type === 'text' ? '1' : '.55';
    }
    </script>
</body>
</html>
