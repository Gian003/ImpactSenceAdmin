<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ImpactSense — Accept Invitation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Genos:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            background: #F2F0F0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Genos', sans-serif;
            padding: 1.5rem;
        }

        .shell {
            display: flex;
            width: 820px;
            max-width: 96vw;
            min-height: 480px;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 8px 40px rgba(0,0,0,.18);
        }

        /* Left panel */
        .left-panel {
            width: 38%;
            background: #7B1A2E;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 28px;
            gap: 16px;
            text-align: center;
        }
        .left-panel img { width: 90px; height: 90px; object-fit: contain; }
        .left-panel h2 {
            color: #fff;
            font-size: 1rem;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.4;
        }
        .left-panel p {
            color: rgba(255,255,255,.55);
            font-size: .75rem;
            margin: 0;
            line-height: 1.5;
        }

        /* Divider */
        .divider { width: 1px; background: rgba(255,255,255,.15); align-self: stretch; flex-shrink: 0; }

        /* Right panel */
        .right-panel {
            flex: 1;
            background: #7B1A2E;
            padding: 36px 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            background: #F4C5D0;
            color: #7B1A2E;
            margin-bottom: 8px;
        }

        .page-title {
            color: #fff;
            font-size: 1.15rem;
            font-weight: 800;
            margin-bottom: 2px;
        }
        .page-sub {
            color: rgba(255,255,255,.55);
            font-size: .78rem;
            margin-bottom: 22px;
        }

        .form-label { font-size: .78rem; font-weight: 600; color: rgba(255,255,255,.75); margin-bottom: 5px; }

        .form-control {
            background: rgba(255,255,255,.10);
            border: 1px solid rgba(255,255,255,.22);
            border-radius: 8px;
            color: #fff;
            font-size: .88rem;
            padding: 10px 14px;
        }
        .form-control::placeholder { color: rgba(255,255,255,.35); }
        .form-control:focus {
            background: rgba(255,255,255,.14);
            border-color: #F4C5D0;
            box-shadow: 0 0 0 3px rgba(244,197,208,.2);
            color: #fff;
            outline: none;
        }

        .btn-submit {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 30px;
            background: #F4C5D0;
            color: #7B1A2E;
            font-weight: 800;
            font-size: .9rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            cursor: pointer;
            margin-top: 4px;
            transition: background .15s;
        }
        .btn-submit:hover { background: #eaa8b8; }

        .alert-error {
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,100,100,.4);
            color: #fca5a5;
            border-radius: 7px;
            padding: 9px 14px;
            font-size: .83rem;
            margin-bottom: 16px;
        }

        .expiry-note {
            text-align: center;
            font-size: .72rem;
            color: rgba(255,255,255,.35);
            margin-top: 14px;
            margin-bottom: 0;
        }

        @media (max-width: 580px) {
            .left-panel { display: none; }
            .divider { display: none; }
        }
    </style>
</head>
<body>

<div class="shell">

    {{-- Left branding --}}
    <div class="left-panel">
        <img src="{{ asset('images/pnp_urdaneta_logo.png') }}" alt="PNP Urdaneta"
             onerror="this.style.display='none'">
        <h2>PNP Urdaneta<br>ImpactSense</h2>
        <p>Motorcycle Accident Detection &amp; Response System</p>
    </div>

    <div class="divider"></div>

    {{-- Right form --}}
    <div class="right-panel">

        <div class="mb-3">
            <span class="role-badge">
                {{ $invitation->role === 'toc' ? 'TOC Officer' : 'Investigation Officer' }}
            </span>
            <div class="page-title">Set Up Your Account</div>
            <div class="page-sub">Invited as <strong style="color:rgba(255,255,255,.8);">{{ $invitation->email }}</strong></div>
        </div>

        @if($errors->any())
            <div class="alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.invitations.accept', $invitation->token) }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" value="{{ old('full_name') }}"
                       class="form-control" placeholder="PCpl Juan Dela Cruz" required>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label">Badge Number</label>
                    <input type="text" name="badge_number" value="{{ old('badge_number') }}"
                           class="form-control" placeholder="PNP-12345" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Rank</label>
                    <input type="text" name="rank" value="{{ old('rank') }}"
                           class="form-control" placeholder="Police Corporal" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password"
                       class="form-control" placeholder="Minimum 8 characters" required>
            </div>

            <div class="mb-4">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirmation"
                       class="form-control" placeholder="Repeat password" required>
            </div>

            <button type="submit" class="btn-submit">Create My Account</button>
        </form>

        <p class="expiry-note">
            This invitation expires {{ $invitation->expires_at->diffForHumans() }}.
        </p>
    </div>

</div>

</body>
</html>
