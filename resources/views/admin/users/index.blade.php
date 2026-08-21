@extends('admin.layouts.app')
@section('title', 'User Management')

@section('content')

<div class="row g-3">

    {{-- TOC Officers --}}
    <div class="col-12">
        <div class="card-panel">
            <div class="card-panel-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="status-badge badge-toc">TOC</span>
                    TOC Officers
                    <span style="color:#94a3b8; font-size:.8rem; font-weight:400;">({{ $tocOfficers->count() }})</span>
                </div>
                <a href="{{ route('admin.invitations.index') }}"
                   style="font-size:.8rem; color:#3b82f6; text-decoration:none;">
                    + Invite TOC Officer
                </a>
            </div>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Badge</th>
                            <th>Rank</th>
                            <th>Email</th>
                            <th>Unit</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tocOfficers as $officer)
                        <tr>
                            <td class="fw-semibold">{{ $officer->full_name }}</td>
                            <td style="color:#64748b;">{{ $officer->badge_number }}</td>
                            <td>{{ $officer->rank }}</td>
                            <td style="color:#64748b;">{{ $officer->email }}</td>
                            <td>{{ $officer->unit_assignment ?? '—' }}</td>
                            <td>
                                <span class="status-badge {{ $officer->deleted_at ? 'badge-inactive' : 'badge-active' }}">
                                    {{ $officer->deleted_at ? 'Inactive' : 'Active' }}
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.users.toggle-toc', $officer) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-sm {{ $officer->deleted_at ? 'btn-outline-success' : 'btn-outline-danger' }}"
                                            style="font-size:.75rem; padding:3px 10px;"
                                            onclick="return confirm('Are you sure?')">
                                        {{ $officer->deleted_at ? 'Reactivate' : 'Deactivate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4" style="color:#94a3b8;">
                                No TOC officers yet. Invite one to get started.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Investigation Officers --}}
    <div class="col-12">
        <div class="card-panel">
            <div class="card-panel-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="status-badge badge-inv">INV</span>
                    Investigation Officers
                    <span style="color:#94a3b8; font-size:.8rem; font-weight:400;">({{ $invOfficers->count() }})</span>
                </div>
                <a href="{{ route('admin.invitations.index') }}"
                   style="font-size:.8rem; color:#3b82f6; text-decoration:none;">
                    + Invite Investigation Officer
                </a>
            </div>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Badge</th>
                            <th>Rank</th>
                            <th>Email</th>
                            <th>Unit</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invOfficers as $officer)
                        <tr>
                            <td class="fw-semibold">{{ $officer->full_name }}</td>
                            <td style="color:#64748b;">{{ $officer->badge_number }}</td>
                            <td>{{ $officer->rank }}</td>
                            <td style="color:#64748b;">{{ $officer->email }}</td>
                            <td>{{ $officer->unit_assignment ?? '—' }}</td>
                            <td>
                                <span class="status-badge {{ $officer->deleted_at ? 'badge-inactive' : 'badge-active' }}">
                                    {{ $officer->deleted_at ? 'Inactive' : 'Active' }}
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.users.toggle-investigation', $officer) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-sm {{ $officer->deleted_at ? 'btn-outline-success' : 'btn-outline-danger' }}"
                                            style="font-size:.75rem; padding:3px 10px;"
                                            onclick="return confirm('Are you sure?')">
                                        {{ $officer->deleted_at ? 'Reactivate' : 'Deactivate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4" style="color:#94a3b8;">
                                No investigation officers yet.
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
