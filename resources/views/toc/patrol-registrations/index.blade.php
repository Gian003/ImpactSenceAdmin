@extends('toc.layouts.app')

@section('title', 'Patrol Registrations')

@section('content')

{{-- Flash messages --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible py-2 mb-4" style="font-size:.84rem;">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible py-2 mb-4" style="font-size:.84rem;">
    {{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ── PENDING REQUESTS ──────────────────────────────────────────────────────── --}}
<h6 class="fw-bold mb-3" style="color:#111827;">
    Pending Requests
    @if($pending->count())
    <span class="badge rounded-pill ms-2" style="background:#e53e3e; font-size:.72rem;">
        {{ $pending->count() }}
    </span>
    @endif
</h6>

@if($pending->isEmpty())
<div class="card border rounded-3 mb-5 p-4 text-center" style="border-color:#d1dde6 !important; color:#6b7280; font-size:.85rem;">
    No pending registration requests.
</div>
@else
<div class="row g-3 mb-5">
    @foreach($pending as $reg)
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
                        @if($reg->phone_number)
                        <div style="font-size:.8rem; color:#6b7280;">{{ $reg->phone_number }}</div>
                        @endif
                    </div>
                    <span class="badge rounded-pill" style="background:#f59e0b; color:#fff; font-size:.72rem;">
                        PENDING
                    </span>
                </div>

                <div class="text-muted mb-3" style="font-size:.78rem;">
                    Submitted {{ $reg->created_at->diffForHumans() }}
                </div>

                {{-- Approve form --}}
                <form method="POST"
                      action="{{ route('toc.patrol-registrations.approve', $reg) }}"
                      class="mb-2">
                    @csrf
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <input type="text" name="badge_number"
                                   class="form-control form-control-sm"
                                   placeholder="Badge No. (e.g. P01-001)"
                                   style="border-color:#c8d8e4; font-size:.8rem;"
                                   required>
                        </div>
                        <div class="col-6">
                            <input type="text" name="rank"
                                   class="form-control form-control-sm"
                                   placeholder="Rank (e.g. Patrolman)"
                                   style="border-color:#c8d8e4; font-size:.8rem;"
                                   required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm w-100 text-white fw-semibold"
                            style="background:#2a7c5b; font-size:.82rem;">
                        ✓ Approve &amp; Create Account
                    </button>
                </form>

                {{-- Reject form --}}
                <form method="POST"
                      action="{{ route('toc.patrol-registrations.reject', $reg) }}">
                    @csrf
                    <div class="input-group input-group-sm">
                        <input type="text" name="rejection_reason"
                               class="form-control"
                               placeholder="Rejection reason…"
                               style="border-color:#c8d8e4; font-size:.78rem;"
                               required>
                        <button type="submit" class="btn btn-sm text-white fw-semibold"
                                style="background:#e53e3e; font-size:.78rem;">
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
                        @if($reg->status === 'approved')
                            <span class="badge rounded-pill" style="background:#2a7c5b; font-size:.72rem;">APPROVED</span>
                        @else
                            <span class="badge rounded-pill" style="background:#e53e3e; font-size:.72rem;"
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
