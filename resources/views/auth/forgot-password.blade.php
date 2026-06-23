<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ImpactSense — Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/auth/login.css') }}" rel="stylesheet">
</head>
<body class="min-vh-100 d-flex align-items-center justify-content-center p-3">

    <div class="login-card shadow p-4 p-md-5 w-100" style="max-width:480px;">

        {{-- Logo + title --}}
        <div class="text-center mb-4">
            <img src="{{ asset('images/pnp-urdaneta.png') }}" alt="PNP Urdaneta"
                 width="80" height="80" style="object-fit:contain;" class="mb-3">
            <h1 class="fw-bold mb-1" style="font-size:1.25rem; color:#1a3a4f;">Forgot Password</h1>
            <p class="mb-0" style="font-size:.85rem; color:#4a7a96;">
                Enter your registered email address and we will<br>send you a password reset link.
            </p>
        </div>

        {{-- Success message --}}
        @if(session('status'))
        <div class="alert py-2 mb-4 text-center"
             style="background:#e8f5e9; border:1px solid #a5d6a7; color:#2e7d32; font-size:.85rem;">
            {{ session('status') }}
        </div>
        @endif

        {{-- Error messages --}}
        @if($errors->any())
        <div class="alert alert-danger py-2 mb-4" style="font-size:.85rem;">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="input-group mb-4">
                <span class="input-group-text border-end-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M22 4H2v16h20V4z"/><path d="m2 4 10 9 10-9"/>
                    </svg>
                </span>
                <input type="email" name="email" class="form-control border-start-0 ps-0"
                       placeholder="Email Address" value="{{ old('email') }}"
                       autocomplete="email" required autofocus>
            </div>

            <button type="submit" class="btn btn-login w-100 py-2 mb-3">
                Send Reset Link
            </button>
        </form>

        <div class="text-center">
            <a href="{{ route('login') }}" style="font-size:.85rem; color:#2c5f7a;" class="text-decoration-none">
                ← Back to Login
            </a>
        </div>

    </div>

</body>
</html>
