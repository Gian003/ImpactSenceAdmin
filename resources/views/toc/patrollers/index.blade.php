@extends('toc.layouts.app')

@section('title', 'Patrollers Unit')

@section('content')

<h6 class="fw-bold mb-3" style="color:#111827;">Registered Patrollers</h6>

<div class="card border rounded-3" style="border-color:#d1dde6 !important;">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.83rem;">
            <thead class="table-light">
                <tr>
                    <th class="fw-bold border-bottom">Full Name</th>
                    <th class="fw-bold border-bottom">Location</th>
                    <th class="fw-bold border-bottom">Status</th>
                    <th class="fw-bold border-bottom">Contact Number</th>
                </tr>
            </thead>
            <tbody>
                @forelse($patrollers ?? [] as $patroller)
                <tr>
                    <td>{{ $patroller->full_name }}</td>
                    <td>{{ $patroller->location }}</td>
                    <td>{{ $patroller->status }}</td>
                    <td>{{ $patroller->contact_number }}</td>
                </tr>
                @empty
                <tr><td>Vladimir V. Lalas</td><td>Brgy. Tipuso, Urdaneta City, Pangasinan</td><td>Online</td><td>09123645871</td></tr>
                <tr><td>Anabel T. Ganancial</td><td>Brgy. Nancayasan, Urdaneta City, Pangasinan</td><td>Online</td><td>09663322558</td></tr>
                <tr><td>Jesus D. Tambalo</td><td>Brgy. Mabanogbog, Urdaneta City, Pangasinan</td><td>Online</td><td>09875461235</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
