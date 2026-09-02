@extends('toc.layouts.app')

@section('title', 'Patrol Registrations')

@section('content')

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible py-2 mb-4" style="font-size:.84rem;">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible py-2 mb-4" style="font-size:.84rem;">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- How-to guide — the roster check is automatic, but the photo comparison
     is a human judgment call the admin has to actually perform; spelling out
     what to look for keeps that judgment consistent across different admins.
     <details>/<summary> is native HTML (keyboard-operable, announced as
     expandable with no extra ARIA) — see WCAG 3.3.2 (Labels or Instructions). --}}
    <details class="card border rounded-3 mb-4" style="border-color:#d1dde6 !important;">
        <summary style="cursor:pointer; padding:14px 18px; font-weight:700; font-size:1.10rem; color:#111827;">
            How to review a pending registration
        </summary>
        <div style="padding:2px 18px 18px 18px; border-top:1px solid #eef2f6;">
            <ol style="margin:14px 0 0 0; padding-left:20px; font-size:1rem; color:#374151; line-height:1.7;">
                <li>
                    <strong>Badge and rank are already verified</strong> — the app only accepted this
                    submission because its badge number matched an <strong>Active</strong> entry in
                    <a href="{{ route('toc.personnel-roster.index') }}">Personnel Roster</a>, and the
                    rank shown came from that roster entry, not from the applicant. You don't need to
                    re-check the badge number itself.
                </li>
                <li>
                    <strong>Compare the two photos.</strong> "Submitted Photo" was taken live with the
                    applicant's camera at the moment they registered (not chosen from their gallery);
                    "Roster Reference" is the photo on file for that badge number. Check that they're
                    plausibly the same person before approving.
                </li>
                <li>
                    If <strong>Roster Reference</strong> shows "No reference photo on file," there's
                    nothing to compare against — confirm the officer's identity another way (in
                    person, by phone, or by adding a reference photo to the roster first) before
                    approving.
                </li>
                <li>
                    <strong>Approve</strong> immediately creates their patrol account with the
                    credentials they registered with. <strong>Reject</strong> requires a short reason,
                    which the applicant can see if they try registering again — be specific enough
                    that they know what to fix.
                </li>
            </ol>
        </div>
    </details>

    {{-- ── PENDING REQUESTS ──────────────────────────────────────────────────────── --}}
    <h6 class="fw-bold mb-3" style="color:#111827;">
        Pending Requests
        @if ($pending->count())
            <span class="badge rounded-pill ms-2" style="background:#b91c1c; font-size:.72rem;">
                {{ $pending->count() }}
            </span>
        @endif
    </h6>

    @if ($pending->isEmpty())
        <div class="card border rounded-3 mb-5 p-4 text-center"
            style="border-color:#d1dde6 !important; color:#6b7280; font-size:.85rem;">
            No pending registration requests.
        </div>
    @else
        <div class="row g-3 mb-5">
            @foreach ($pending as $reg)
                <div class="col-md-6">
                    <div class="card border rounded-3 h-100" style="border-color:#d1dde6 !important;">
                        <div class="card-body">

                            {{-- Header --}}
                            <div class="d-flex align-items-start justify-content-between mb-3">
                                <div>
                                    <div class="fw-bold" style="font-size:.95rem; color:#111827;">
                                        {{ $reg->full_name }}
                                    </div>
                                    <div style="font-size:.8rem; color:#6b7280;">{{ $reg->email }}</div>
                                    @if ($reg->phone_number)
                                        <div style="font-size:.8rem; color:#6b7280;">{{ $reg->phone_number }}</div>
                                    @endif
                                </div>
                                <span class="badge rounded-pill" style="background:#b45309; color:#fff; font-size:.72rem;">
                                    PENDING
                                </span>
                            </div>

                            <div class="text-muted mb-3" style="font-size:.78rem;">
                                Submitted {{ $reg->created_at->diffForHumans() }}
                            </div>

                            {{-- badge_number/rank were validated against the personnel
                     roster at submission time (see PatrolRegistrationController::store)
                     — shown here for confirmation, not typed in fresh. --}}
                            <div class="d-flex gap-2 mb-3">
                                <span
                                    style="font-family:monospace; background:#f1f5f9; color:#1e293b; padding:3px 10px; border-radius:6px; font-size:.8rem; font-weight:600;">
                                    {{ $reg->badge_number ?? 'No badge number' }}
                                </span>
                                <span
                                    style="background:#fce7f3; color:#7B1A2E; padding:3px 10px; border-radius:6px; font-size:.8rem; font-weight:600;">
                                    {{ $reg->rank ?? '—' }}
                                </span>
                            </div>

                            {{-- Photo comparison — the applicant's live-captured photo
                     next to the roster's reference photo for the same badge
                     number, so TOC staff have something to actually compare
                     instead of approving on a typed name alone. --}}
                            <div class="row g-2 mb-3">
                                <div class="col-6 text-center">
                                    <div
                                        style="font-size:.7rem; color:#6b7280; margin-bottom:4px; text-transform:uppercase; letter-spacing:.04em;">
                                        Submitted Photo</div>
                                    @if ($reg->photo_path)
                                        <img src="{{ asset('storage/' . $reg->photo_path) }}"
                                            alt="Submitted registration photo"
                                            style="width:100%; max-width:140px; aspect-ratio:1; object-fit:cover; border-radius:8px; border:1px solid #d1dde6;">
                                    @else
                                        <div
                                            style="width:100%; max-width:140px; aspect-ratio:1; margin:0 auto; display:flex; align-items:center; justify-content:center; background:#f1f5f9; color:#94a3b8; border-radius:8px; font-size:.72rem;">
                                            None
                                        </div>
                                    @endif
                                </div>
                                <div class="col-6 text-center">
                                    <div
                                        style="font-size:.7rem; color:#6b7280; margin-bottom:4px; text-transform:uppercase; letter-spacing:.04em;">
                                        Roster Reference</div>
                                    @if ($reg->roster?->reference_photo_path)
                                        <img src="{{ asset('storage/' . $reg->roster->reference_photo_path) }}"
                                            alt="Roster reference photo"
                                            style="width:100%; max-width:140px; aspect-ratio:1; object-fit:cover; border-radius:8px; border:1px solid #d1dde6;">
                                    @else
                                        <div
                                            style="width:100%; max-width:140px; aspect-ratio:1; margin:0 auto; display:flex; align-items:center; justify-content:center; background:#f1f5f9; color:#94a3b8; border-radius:8px; font-size:.72rem; text-align:center; padding:8px;">
                                            No reference photo on file
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Approve form — no fields left to type in; badge_number
                     and rank already came from the roster match above. --}}
                            <form method="POST" action="{{ route('toc.patrol-registrations.approve', $reg) }}"
                                class="mb-2">
                                @csrf
                                <button type="submit" class="btn btn-sm w-100 text-white fw-semibold"
                                    style="background:#2a7c5b; font-size:.82rem;">
                                    ✓ Approve &amp; Create Account
                                </button>
                            </form>

                            {{-- Reject form --}}
                            <form method="POST" action="{{ route('toc.patrol-registrations.reject', $reg) }}">
                                @csrf
                                <div class="input-group input-group-sm">
                                    <input type="text" name="rejection_reason" class="form-control"
                                        placeholder="Rejection reason…" style="border-color:#c8d8e4; font-size:.78rem;"
                                        required>
                                    <button type="submit" class="btn btn-sm text-white fw-semibold"
                                        style="background:#b91c1c; font-size:.78rem;">
                                        ✕ Reject
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ── RECENTLY REVIEWED ────────────────────────────────────────────────────── --}}
    <h6 class="fw-bold mb-3" style="color:#111827;">Recently Reviewed</h6>

    <div class="card border rounded-3" style="border-color:#d1dde6 !important;">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.83rem;">
                <thead class="table-light">
                    <tr>
                        <th class="fw-bold border-bottom">Full Name</th>
                        <th class="fw-bold border-bottom">Email</th>
                        <th class="fw-bold border-bottom">Badge</th>
                        <th class="fw-bold border-bottom">Status</th>
                        <th class="fw-bold border-bottom">Reviewed By</th>
                        <th class="fw-bold border-bottom">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviewed as $reg)
                        <tr>
                            <td>{{ $reg->full_name }}</td>
                            <td>{{ $reg->email }}</td>
                            <td>{{ $reg->badge_number ?? '—' }}</td>
                            <td>
                                @if ($reg->status === 'approved')
                                    <span class="badge rounded-pill"
                                        style="background:#2a7c5b; font-size:.72rem;">APPROVED</span>
                                @else
                                    <span class="badge rounded-pill" style="background:#b91c1c; font-size:.72rem;"
                                        title="{{ $reg->rejection_reason }}">REJECTED</span>
                                @endif
                            </td>
                            <td>{{ $reg->reviewer?->full_name ?? '—' }}</td>
                            <td>{{ $reg->reviewed_at?->format('M d, Y h:i A') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4" style="font-size:.83rem;">
                                No reviews yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
