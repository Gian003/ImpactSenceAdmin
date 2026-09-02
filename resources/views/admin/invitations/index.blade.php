@extends('admin.layouts.app')
@section('title', 'Invitations')

@section('content')

<div class="row g-3">

    {{-- Send invitation form --}}
    <div class="col-lg-4">
        <div class="card-panel">
            <div class="card-panel-header">Send Invitation</div>
            <div class="card-panel-body">
                <form method="POST" action="{{ route('admin.invitations.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.82rem; font-weight:600; color:#374151;">
                            Officer Email
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="form-control" placeholder="officer@pnp-urdaneta.gov.ph" required>
                        <div style="font-size:.75rem; color:#64748b; margin-top:4px;">
                            The invitation link will be sent to this address.
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" style="font-size:.82rem; font-weight:600; color:#374151;">
                            Department Role
                        </label>
                        <select name="role" class="form-select" required>
                            <option value="">— Select role —</option>
                            <option value="toc" {{ old('role') === 'toc' ? 'selected' : '' }}>
                                TOC Officer
                            </option>
                            <option value="investigation" {{ old('role') === 'investigation' ? 'selected' : '' }}>
                                Investigation Officer
                            </option>
                        </select>
                    </div>
                    <button type="submit" class="btn w-100"
                            style="background:#1a2b4a; color:#fff; font-size:.875rem; font-weight:600; padding:10px;">
                        Send Invitation
                    </button>
                </form>

                <div class="mt-3 p-3" style="background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0;">
                    <div style="font-size:.75rem; color:#64748b; line-height:1.5;">
                        <strong style="color:#374151;">How it works</strong><br>
                        The officer receives an email with a secure link valid for <strong>24 hours</strong>.
                        They click it, set their own password, and their account is created automatically.
                        No manual DB entry needed.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Invitations list --}}
    <div class="col-lg-8">
        <div class="card-panel">
            <div class="card-panel-header">
                All Invitations
                <span style="color:#64748b; font-size:.8rem; font-weight:400;">
                    ({{ $invitations->count() }} total)
                </span>
            </div>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Invited By</th>
                            <th>Expires</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invitations as $inv)
                        @php $status = $inv->statusLabel(); @endphp
                        <tr>
                            <td>{{ $inv->email }}</td>
                            <td>
                                <span class="status-badge {{ $inv->role === 'toc' ? 'badge-toc' : 'badge-inv' }}">
                                    {{ $inv->role === 'toc' ? 'TOC' : 'Investigation' }}
                                </span>
                            </td>
                            <td>
                                <span class="status-badge
                                    {{ $status === 'Accepted' ? 'badge-accepted' :
                                       ($status === 'Pending'  ? 'badge-pending'  : 'badge-expired') }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td style="color:#64748b;">{{ $inv->invitedBy?->full_name ?? '—' }}</td>
                            <td style="color:#64748b; font-size:.78rem;">
                                {{ $inv->expires_at->format('M d, Y H:i') }}
                            </td>
                            <td>
                                @if($inv->isPending())
                                <form method="POST" action="{{ route('admin.invitations.destroy', $inv) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            style="font-size:.72rem; padding:2px 8px;"
                                            onclick="return confirm('Revoke this invitation?')">
                                        Revoke
                                    </button>
                                </form>
                                @else
                                    <span style="color:#6b7280; font-size:.78rem;">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4" style="color:#64748b;">
                                No invitations sent yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
