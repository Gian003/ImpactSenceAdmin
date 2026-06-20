@extends('investigation.layouts.app')

@section('title', 'Device Management')

@section('content')

<h6 class="fw-bold mb-3" style="color:#111827;">Registered Riders</h6>

<div class="card border rounded-3" style="border-color:#d1dde6 !important;">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.83rem;">
            <thead class="table-light">
                <tr>
                    <th class="fw-bold border-bottom">Device Model</th>
                    <th class="fw-bold border-bottom">Full Name</th>
                    <th class="fw-bold border-bottom">Age</th>
                    <th class="fw-bold border-bottom">Contact Number</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riders ?? [] as $rider)
                <tr>
                    <td>{{ $rider->device_model }}</td>
                    <td>{{ $rider->full_name }}</td>
                    <td>{{ $rider->age }}</td>
                    <td>{{ $rider->contact_number }}</td>
                </tr>
                @empty
                <tr><td>ITK-BLK4-GRP5-MDL1</td><td>Rester Mendoza</td><td>20</td><td>09123645871</td></tr>
                <tr><td>ITK-BLK4-GRP5-MDL2</td><td>Darnil Castanieto</td><td>20</td><td>09663322558</td></tr>
                <tr><td>ITK-BLK4-GRP5-MDL3</td><td>Gian Rodriguez</td><td>20</td><td>09875461235</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
