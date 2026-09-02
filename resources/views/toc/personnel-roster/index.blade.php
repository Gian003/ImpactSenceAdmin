@extends('toc.layouts.app')

@section('title', 'Personnel Roster')

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

    <p class="text-muted mb-4" style="font-size:.85rem; max-width:680px; color:#475569 !important;">
        The authoritative list new patrol registrations are checked against. Add a badge number here
        <strong>before</strong> the officer registers in the mobile app — a registration is rejected
        automatically unless its badge number matches an active entry here. Adding a reference photo is
        optional but lets TOC staff visually compare it against the photo an applicant submits during
        registration.
    </p>

    {{-- How-to guide — roster maintenance is a prerequisite step that happens
     before, and separately from, the mobile registration it gates, so it's
     easy to forget or get the ordering wrong. <details>/<summary> is native
     HTML (keyboard-operable, announced as expandable with no extra ARIA) —
     see WCAG 3.3.2 (Labels or Instructions). --}}
    <details class="card border-0 rounded-3 mb-4" style="border:1px solid #e8d5d9 !important;">
        <summary style="cursor:pointer; padding:14px 18px; font-weight:700; font-size:1.10rem; color:#1e293b;">
            How to onboard an officer for registration
        </summary>
        <div style="padding:2px 18px 18px 18px; border-top:1px solid #f5eeef;">
            <ol style="margin:14px 0 0 0; padding-left:20px; font-size:1rem; color:#374151; line-height:1.7;">
                <li>
                    <strong>Add the officer here first</strong>, using their exact
                    <strong>Badge Number</strong> — this must match, character-for-character, what
                    they'll later type into the mobile app. A reference photo is optional here but
                    strongly recommended, since it's what makes the photo comparison in
                    <strong>Patrol Registrations</strong> meaningful rather than just a formality.
                </li>
                <li>
                    Only give the officer their badge number once they're listed here as
                    <strong>Active</strong> — the mobile app rejects any registration whose badge
                    number doesn't match an active roster entry, so registering too early will fail.
                </li>
                <li>
                    The officer then registers on their own device: they enter that badge number and
                    take a live photo of themselves in the app. Their rank is filled in automatically
                    from this roster — they don't type it themselves.
                </li>
                <li>
                    That submission appears under <strong>Patrol Registrations → Pending Requests</strong>
                    for a TOC admin to compare photos and approve or reject.
                </li>
                <li>
                    If an officer leaves or is reassigned, click <strong>Deactivate</strong> instead of
                    deleting the row — this blocks any new registration attempt on that badge number
                    while keeping the record (and photo) for reference.
                </li>
            </ol>
        </div>
    </details>

    {{-- Add Personnel form --}}
    <h6 class="fw-bold mb-2" style="color:#1e293b;">Add Personnel</h6>
    <div class="card border-0 rounded-3 mb-4" style="border:1px solid #e8d5d9 !important;">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('toc.personnel-roster.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label" style="font-size:.78rem; color:#475569; font-weight:600;">Badge
                            Number</label>
                        <input type="text" name="badge_number" class="form-control form-control-sm"
                            placeholder="e.g. P01-001"
                            style="border-color:#e8d5d9; font-size:.83rem; border-radius:7px; font-family:monospace;"
                            value="{{ old('badge_number') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size:.78rem; color:#475569; font-weight:600;">Full
                            Name</label>
                        <input type="text" name="full_name" class="form-control form-control-sm"
                            placeholder="e.g. Juan Dela Cruz"
                            style="border-color:#e8d5d9; font-size:.83rem; border-radius:7px;"
                            value="{{ old('full_name') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" style="font-size:.78rem; color:#475569; font-weight:600;">Rank</label>
                        {{-- Fixed dropdown, not free text — rank is a closed, legally
                             defined set under RA 11200, not an open-ended field, so a
                             <select> prevents typos/inconsistent abbreviations that
                             would otherwise silently break the "same person" photo
                             comparison a badge number is supposed to guarantee. --}}
                        <select name="rank" class="form-select form-select-sm"
                            style="border-color:#e8d5d9; font-size:.83rem; border-radius:7px;" required>
                            <option value="" disabled {{ old('rank') ? '' : 'selected' }}>Select rank…</option>
                            @foreach ($ranks as $rankOption)
                                <option value="{{ $rankOption }}" {{ old('rank') === $rankOption ? 'selected' : '' }}>
                                    {{ $rankOption }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" style="font-size:.78rem; color:#475569; font-weight:600;">Reference
                            Photo</label>
                        <input type="file" name="photo" accept="image/*" class="form-control form-control-sm"
                            style="border-color:#e8d5d9; font-size:.8rem; border-radius:7px;">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm text-white fw-semibold w-100"
                            style="background:#7B1A2E; border-color:#7B1A2E; font-size:.82rem; border-radius:7px;">
                            Add
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Roster table --}}
    <h6 class="fw-bold mb-2" style="color:#1e293b;">Roster ({{ $roster->count() }})</h6>
    <div class="card border-0 rounded-3 overflow-hidden" style="border:1px solid #e8d5d9 !important;">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead>
                    <tr>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Photo</th>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Badge Number</th>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Full Name</th>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Rank</th>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Status</th>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roster as $r)
                        <tr>
                            <td style="padding:8px 16px; border-bottom:1px solid #f5eeef;">
                                {{-- Clickable through to the full-size original (not just a
                                     bigger thumbnail) — a 38px circle was too small to actually
                                     confirm identity against; opening the full photo in a new
                                     tab lets staff zoom instead of squinting at a thumbnail. --}}
                                @if ($r->reference_photo_path)
                                    <a href="{{ asset('storage/' . $r->reference_photo_path) }}" target="_blank"
                                        rel="noopener" title="View full-size reference photo for {{ $r->full_name }}">
                                        <img src="{{ asset('storage/' . $r->reference_photo_path) }}"
                                            alt="Reference photo for {{ $r->full_name }}"
                                            style="width:56px; height:56px; border-radius:50%; object-fit:cover; border:2px solid #e8d5d9; cursor:pointer;">
                                    </a>
                                @else
                                    <span
                                        style="display:inline-flex; align-items:center; justify-content:center; width:56px; height:56px; border-radius:50%; background:#f1f5f9; color:#94a3b8; font-size:.72rem;">
                                        N/A
                                    </span>
                                @endif
                            </td>
                            <td
                                style="padding:11px 16px; color:#1e293b; font-weight:600; border-bottom:1px solid #f5eeef; font-family:monospace;">
                                {{ $r->badge_number }}
                            </td>
                            <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">
                                {{ $r->full_name }}</td>
                            <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">
                                {{ $r->rank }}</td>
                            <td style="padding:11px 16px; border-bottom:1px solid #f5eeef;">
                                @if ($r->is_active)
                                    <span
                                        style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; background:#d1fae5; color:#065f46;">Active</span>
                                @else
                                    <span
                                        style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; background:#f1f5f9; color:#475569;">Inactive</span>
                                @endif
                            </td>
                            <td style="padding:11px 16px; border-bottom:1px solid #f5eeef;">
                                <form method="POST" action="{{ route('toc.personnel-roster.toggle', $r) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm"
                                        style="font-size:.74rem; padding:2px 10px; border:1px solid #cbd5e1; background:#fff; color:#475569; border-radius:6px;">
                                        {{ $r->is_active ? 'Deactivate' : 'Reactivate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4" style="font-size:.84rem;">
                                No personnel on the roster yet — add someone above before they try to register.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
