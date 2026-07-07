<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ImpactSense — Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/auth/login.css') }}" rel="stylesheet">
</head>
<body class="min-vh-100 d-flex align-items-center justify-content-center p-3">

    <div class="login-card shadow p-4 p-md-5 w-100" style="max-width:680px;">
        <div class="d-flex gap-4">

            {{-- LEFT PANEL --}}
            <div class="d-flex flex-column align-items-center justify-content-center text-center pe-3" style="flex:1;">
                <img src="{{ asset('images/pnp_urdaneta_logo.png') }}" alt="Urdaneta City Police Station"
                     width="140" height="140" style="object-fit:contain;"
                     class="mb-3" onerror="this.style.display='none'">
                <h1 class="fw-black mb-2" style="font-size:1.4rem; letter-spacing:.1em; color:#1a3a4f;">
                    IMPACTSENSE
                </h1>
                <p class="mb-2 lh-sm" style="font-size:.85rem; color:#2c5f7a;">
                    Real- time Accident Monitoring<br>and Response System
                </p>
                <p class="fw-bold mb-0" style="font-size:.85rem; color:#1a3a4f;">PNP Urdaneta</p>
            </div>

            {{-- DIVIDER --}}
            <div class="divider mx-1"></div>

            {{-- RIGHT PANEL --}}
            <div class="d-flex flex-column justify-content-center" style="flex:1;">

                <div class="d-flex align-items-center gap-2 mb-1">
                    <img src="{{ asset('images/impactsense_logo.png') }}" alt="ImpactSense"
                         width="38" height="38" style="object-fit:contain;"
                         onerror="this.style.display='none'">
                    <h2 class="fw-bold mb-0" style="color:#1a3a4f;">Welcome Back!</h2>
                </div>
                <p class="mb-4" style="font-size:.88rem; color:#4a7a96;">Sign in your account</p>

                @if(session('status'))
                <div class="alert py-2 mb-3 text-center"
                     style="background:#e8f5e9; border:1px solid #a5d6a7; color:#2e7d32; font-size:.85rem;">
                    {{ session('status') }}
                </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger py-2 mb-3" style="font-size:.85rem;">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    {{-- Email --}}
                    <div class="input-group mb-3">
                        <span class="input-group-text border-end-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M22 4H2v16h20V4z"/><path d="m2 4 10 9 10-9"/>
                            </svg>
                        </span>
                        <input type="email" name="email" class="form-control border-start-0 ps-0"
                               placeholder="Email Address" value="{{ old('email') }}"
                               autocomplete="email" required>
                    </div>

                    {{-- Password --}}
                    <div class="input-group mb-3">
                        <span class="input-group-text border-end-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round" viewBox="0 0 24 24">
                                <rect x="3" y="11" width="18" height="11" rx="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <input type="password" name="password" id="password"
                               class="form-control border-start-0 border-end-0 ps-0"
                               placeholder="Password" autocomplete="current-password" required>
                        <button type="button" class="input-group-text bg-transparent border-start-0"
                                onclick="toggleVis('password', this)" style="border-color:#7aafc8;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                                 stroke="#4a7a96" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Remember + Forgot --}}
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label" for="remember"
                                   style="font-size:.85rem; color:#2c5f7a;">
                                Keep me Logged in
                            </label>
                        </div>
                        <a href="{{ route('password.request') }}" style="font-size:.85rem; color:#2c5f7a;" class="text-decoration-none">
                            Forgot Password?
                        </a>
                    </div>

                    <button type="submit" class="btn btn-login w-100 py-2">Log in</button>
                </form>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleVis(id, btn) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
            btn.querySelector('svg').style.opacity = input.type === 'text' ? '0.4' : '1';
        }
    </script>
</body>
</html>
