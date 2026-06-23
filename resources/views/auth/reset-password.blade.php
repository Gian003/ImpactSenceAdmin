<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ImpactSense — Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/auth/login.css') }}" rel="stylesheet">
</head>
<body class="min-vh-100 d-flex align-items-center justify-content-center p-3">

    <div class="login-card shadow p-4 p-md-5 w-100" style="max-width:480px;">

        {{-- Logo + title --}}
        <div class="text-center mb-4">
            <img src="{{ asset('images/pnp-urdaneta.png') }}" alt="PNP Urdaneta"
                 width="80" height="80" style="object-fit:contain;" class="mb-3">
            <h1 class="fw-bold mb-1" style="font-size:1.25rem; color:#1a3a4f;">Reset Password</h1>
            <p class="mb-0" style="font-size:.85rem; color:#4a7a96;">
                Enter your new password below.
            </p>
        </div>

        {{-- Error messages --}}
        @if($errors->any())
        <div class="alert alert-danger py-2 mb-4" style="font-size:.85rem;">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            {{-- Hidden fields --}}
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ request('email') }}">

            {{-- New password --}}
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
                       placeholder="New Password" autocomplete="new-password" required>
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

            {{-- Confirm password --}}
            <div class="input-group mb-4">
                <span class="input-group-text border-end-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round" viewBox="0 0 24 24">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </span>
                <input type="password" name="password_confirmation" id="password_confirmation"
                       class="form-control border-start-0 border-end-0 ps-0"
                       placeholder="Confirm New Password" autocomplete="new-password" required>
                <button type="button" class="input-group-text bg-transparent border-start-0"
                        onclick="toggleVis('password_confirmation', this)" style="border-color:#7aafc8;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                         stroke="#4a7a96" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                        <line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                </button>
            </div>

            <button type="submit" class="btn btn-login w-100 py-2 mb-3">
                Reset Password
            </button>
        </form>

        <div class="text-center">
            <a href="{{ route('login') }}" style="font-size:.85rem; color:#2c5f7a;" class="text-decoration-none">
                ← Back to Login
            </a>
        </div>

    </div>

    <script>
        function toggleVis(id, btn) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
            btn.querySelector('svg').style.opacity = input.type === 'text' ? '0.4' : '1';
        }
    </script>

</body>
</html>
