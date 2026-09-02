<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ImpactSense Command — @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin/layout.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>

<div class="d-flex" style="min-height:100vh;">

    {{-- SIDEBAR --}}
    <aside class="sidebar d-flex flex-column">

        {{-- Logo --}}
        <div class="d-flex align-items-center gap-2 px-3 py-3 border-bottom border-white border-opacity-10">
            <img src="{{ asset('images/pnp_urdaneta_logo.png') }}" alt="PNP Urdaneta"
                 width="46" height="46" style="object-fit:contain; flex-shrink:0;">
            <div class="text-white fw-bold lh-sm" style="font-size:.82rem; letter-spacing:.05em;">
                PNP URDANETA<br>
                <span style="font-size:.68rem; opacity:.6; font-weight:400;">Command Center</span>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-grow-1 py-3">
            <a href="{{ route('admin.dashboard') }}"
               class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
                Overview
            </a>

            <a href="{{ route('admin.users.index') }}"
               class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                User Management
            </a>

            <a href="{{ route('admin.invitations.index') }}"
               class="nav-link {{ request()->routeIs('admin.invitations*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
                Invitations
                @php $pendingInvites = \App\Models\AdminInvitation::whereNull('accepted_at')->where('expires_at', '>', now())->count(); @endphp
                @if($pendingInvites)
                    <span class="badge rounded-pill ms-auto"
                          style="background:#F4C5D0; color:#7B1A2E; font-size:.65rem; padding:.2rem .5rem;">
                        {{ $pendingInvites }}
                    </span>
                @endif
            </a>

            {{-- Divider --}}
            <div style="height:1px; background:rgba(255,255,255,.08); margin:10px 16px;"></div>
            <div style="padding:6px 20px 4px; font-size:.68rem; color:rgba(255,255,255,.35); letter-spacing:.08em; text-transform:uppercase;">
                Quick Access
            </div>

            <a href="{{ route('toc.dashboard') }}" target="_blank"
               class="nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                </svg>
                TOC Dashboard ↗
            </a>

            <a href="{{ route('investigation.dashboard') }}" target="_blank"
               class="nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                Investigation ↗
            </a>
        </nav>

        {{-- User info + logout --}}
        <div class="px-3 pt-3 pb-2 border-top border-white border-opacity-10">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div style="width:34px; height:34px; border-radius:50%; background:rgba(255,255,255,.15);
                            display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                         stroke="#F4C5D0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <div class="text-white lh-sm" style="overflow:hidden;">
                    <div style="font-size:.8rem; font-weight:600; letter-spacing:.03em;">
                        {{ Auth::guard('admin')->user()->rank }}
                    </div>
                    <div style="font-size:.7rem; opacity:.55; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ Auth::guard('admin')->user()->full_name }}
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit"
                        class="btn btn-sm w-100 text-start d-flex align-items-center gap-2"
                        style="color:rgba(255,255,255,.55); background:transparent; border:1px solid rgba(255,255,255,.12); font-size:.75rem; padding:6px 10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Sign out
                </button>
            </form>
        </div>
    </aside>

    {{-- MAIN CONTENT --}}
    <div class="main-content d-flex flex-column">

        {{-- Top bar --}}
        <div class="topbar">
            <div style="font-weight:700; font-size:.95rem; color:#7B1A2E; letter-spacing:.04em; text-transform:uppercase;">@yield('title', 'Dashboard')</div>
            <div style="font-size:.78rem; color:#64748b;">
                ImpactSense Command Center &mdash; PNP Urdaneta
            </div>
        </div>

        {{-- Flash messages --}}
        <div class="px-4 pt-3">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                    {{ $errors->first() }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        </div>

        {{-- Page content --}}
        <div class="flex-grow-1 p-4">
            @yield('content')
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
