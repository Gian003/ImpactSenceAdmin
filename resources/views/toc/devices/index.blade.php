@extends('toc.layouts.app')

@section('title', 'Device Management')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .dm-stat {
            background: #fff;
            border: 1px solid #e8d5d9;
            border-radius: 10px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .dm-stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .dm-stat-val {
            font-size: 1.55rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1;
        }

        .dm-stat-lbl {
            font-size: .75rem;
            color: #64748b;
            margin-top: 2px;
        }

        .dm-chart-card {
            background: #fff;
            border: 1px solid #e8d5d9;
            border-radius: 10px;
            overflow: hidden;
        }

        .dm-chart-header {
            padding: 14px 18px;
            border-bottom: 1px solid #f5eeef;
            font-weight: 600;
            font-size: .875rem;
            color: #1e293b;
        }

        .dm-chart-body {
            padding: 18px;
        }
    </style>
@endpush

@section('content')

    {{-- Stat summary --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="dm-stat">
                <div class="dm-stat-icon" style="background:#ede9fe;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" stroke="#4c1d95"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <circle cx="12" cy="7" r="4" />
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    </svg>
                </div>
                <div>
                    <div class="dm-stat-val">{{ $totalRiders }}</div>
                    <div class="dm-stat-lbl">Registered Riders</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dm-stat">
                <div class="dm-stat-icon" style="background:#fce7f3;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" stroke="#9d174d"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M12 2a9 9 0 0 1 9 9v1H3v-1a9 9 0 0 1 9-9z" />
                        <path d="M3 12v2a9 9 0 0 0 18 0v-2" />
                        <path d="M9 21h6" />
                    </svg>
                </div>
                <div>
                    <div class="dm-stat-val">{{ $totalDevices }}</div>
                    <div class="dm-stat-lbl">Total Devices</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dm-stat">
                <div class="dm-stat-icon" style="background:#d1fae5;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" stroke="#065f46"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                </div>
                <div>
                    <div class="dm-stat-val">{{ $activeDevices }}</div>
                    <div class="dm-stat-lbl">Active Devices</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dm-stat">
                <div class="dm-stat-icon" style="background:#e0f2fe;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" stroke="#0c4a6e"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                    </svg>
                </div>
                <div>
                    <div class="dm-stat-val">{{ $pairedDevices }}</div>
                    <div class="dm-stat-lbl">Paired Devices</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts row --}}
    <div class="row g-3 mb-4">

        {{-- Riders & Devices trend --}}
        <div class="col-lg-7">
            {{-- h-100 (real Bootstrap utility — the old "h-80" here matched
                 nothing, Bootstrap's scale only goes 25/50/75/100, so this
                 card was never actually being stretched) + d-flex flex-column
                 makes the card fill the row's full stretched height (Bootstrap
                 rows are flex with align-items:stretch by default), and
                 flex-grow-1 on the body below lets it absorb that extra
                 height instead of leaving empty space below a fixed-size
                 header. --}}
            <div class="dm-chart-card h-100 d-flex flex-column">
                <div class="dm-chart-header">Registrations — Last 7 Days</div>
                <div class="dm-chart-body flex-grow-1">
                    <canvas id="regTrendChart" height="80"></canvas>
                </div>
            </div>
        </div>

        {{-- Device status doughnut --}}
        <div class="col-lg-5">
            <div class="dm-chart-card h-100 d-flex flex-column">
                <div class="dm-chart-header">Device Status</div>
                <div class="dm-chart-body flex-grow-1 d-flex align-items-center justify-content-center"
                    style="min-height:230px;">
                    <canvas id="deviceStatusChart" style="max-height:200px;"></canvas>
                </div>
            </div>
        </div>

    </div>

    {{-- Incidents trend --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="dm-chart-card">
                <div class="dm-chart-header">Incident Reports — Last 7 Days</div>
                <div class="dm-chart-body">
                    <canvas id="incidentTrendChart" height="50"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="alert alert-dismissible py-2 mb-3" role="alert"
            style="background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; font-size:.84rem; border-radius:8px;">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-dismissible py-2 mb-3" role="alert"
            style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; font-size:.84rem; border-radius:8px;">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- How-to guide — the register → provision → pair sequence isn't obvious:
     a physical unit and its database record only become "the same device"
     because someone typed the same device_code into both places. Using
     <details>/<summary> (native HTML, not a custom JS accordion) so it's
     keyboard-operable and announced as an expandable section to screen
     readers with no extra ARIA needed — see WCAG 3.3.2 (Labels or
     Instructions): a multi-step process like this is exactly the case
     that success criterion exists for. --}}
    <details class="dm-chart-card mb-4">
        <summary style="cursor:pointer; padding:14px 18px; font-weight:700; font-size:.88rem; color:#1e293b;">
            How to register and set up a new device
        </summary>
        <div style="padding:2px 18px 18px 18px; border-top:1px solid #f5eeef;">
            <ol style="margin:14px 0 0 0; padding-left:20px; font-size:1rem; color:#374151; line-height:1.7;">
                <li>
                    <strong>Register the device below</strong> — enter the <code>device_code</code>
                    printed on the physical unit (Model and Firmware are optional notes for your
                    own records). This only creates a record on this website; it doesn't touch
                    the physical device at all.
                </li>
                <li>
                    Once registered, the device appears in <strong>Unlinked Devices</strong> below
                    with an auto-generated <strong>Pairing Key</strong> — click <em>Copy</em> to
                    grab it.
                </li>
                <li>
                    <strong>Plug the physical device into a computer via USB</strong>, open a
                    serial terminal at 115200 baud, and reset it. Within the first few seconds
                    it accepts a command:
                    <div
                        style="font-family:monospace; background:#f1f5f9; color:#1e293b; padding:8px 12px; border-radius:6px; margin:6px 0; font-size:.85rem; display:inline-block;">
                        SET &lt;device_code&gt;
                    </div>
                    <br>
                    using the <em>exact same</em> device_code you registered in step 1 — that's
                    what makes the physical unit and the database record "the same device."
                    The pairing key from step 2 is <strong>not</strong> needed for this step.
                </li>
                <li>
                    Give the <strong>device code</strong> and <strong>pairing key</strong> to the
                    rider — they enter both in the mobile app's device pairing screen to link it
                    to their account.
                </li>
            </ol>
            <p style="font-size:.85rem; color:#64748b; margin:14px 0 0 0;">
                Registering and provisioning can technically happen in either order, but a device
                provisioned before it's registered will fail every API call ("Device not
                registered") until the matching registration exists — registering first avoids
                that.
            </p>
        </div>
    </details>

    {{-- Register Device form --}}
    <h6 class="fw-bold mb-2" style="color:#1e293b; font-size:.88rem; text-transform:uppercase; letter-spacing:.05em;">
        Register New Device
    </h6>
    <div class="dm-chart-card mb-4">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('toc.devices.store') }}">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label" style="font-size:.78rem; color:#475569; font-weight:600;">Device
                            Code</label>
                        <input type="text" name="device_code" class="form-control form-control-sm"
                            placeholder="e.g. ITK-BLK4-GRP5-MDL1"
                            style="border-color:#e8d5d9; font-size:.83rem; border-radius:7px; font-family:monospace;"
                            value="{{ old('device_code') }}" required>
                        <div style="font-size:.7rem; color:#64748b; margin-top:3px;">Printed on the physical device</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size:.78rem; color:#475569; font-weight:600;">Model</label>
                        <input type="text" name="model" class="form-control form-control-sm"
                            placeholder="e.g. ImpactSense Pro X1"
                            style="border-color:#e8d5d9; font-size:.83rem; border-radius:7px;"
                            value="{{ old('model') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"
                            style="font-size:.78rem; color:#475569; font-weight:600;">Firmware</label>
                        <input type="text" name="firmware_version" class="form-control form-control-sm"
                            placeholder="e.g. 2.0.1" style="border-color:#e8d5d9; font-size:.83rem; border-radius:7px;"
                            value="{{ old('firmware_version') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm text-white fw-semibold px-4 w-100"
                            style="background:#7B1A2E; border-color:#7B1A2E; font-size:.82rem; border-radius:7px;">
                            Register Device
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Unlinked Devices (available for pairing) --}}
    @if (($unlinkedDevices ?? collect())->isNotEmpty())
        <h6 class="fw-bold mb-2" style="color:#1e293b; font-size:.88rem; text-transform:uppercase; letter-spacing:.05em;">
            Unlinked Devices
            <span
                style="font-size:.72rem; font-weight:500; color:#6b7280; margin-left:6px; text-transform:none; letter-spacing:0;">Ready
                to pair via mobile app</span>
        </h6>
        <div class="dm-chart-card mb-4">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:.83rem;">
                    <thead>
                        <tr>
                            <th
                                style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                                Device Code</th>
                            <th
                                style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                                Pairing Key</th>
                            <th
                                style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                                Model</th>
                            <th
                                style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                                Firmware</th>
                            <th
                                style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                                Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($unlinkedDevices as $dev)
                            <tr>
                                <td style="padding:11px 16px; border-bottom:1px solid #f5eeef;">
                                    <span
                                        style="font-family:monospace; background:#f1f5f9; padding:2px 8px; border-radius:5px; font-size:.82rem; color:#1e293b;">
                                        {{ $dev->device_code }}
                                    </span>
                                </td>
                                <td style="padding:11px 16px; border-bottom:1px solid #f5eeef;">
                                    <span class="pairing-key-cell"
                                        style="display:inline-flex; align-items:center; gap:8px;">
                                        <span class="pk-value"
                                            style="font-family:monospace; background:#fce7f3; color:#7B1A2E; padding:2px 10px; border-radius:5px; font-size:.82rem; font-weight:700; letter-spacing:.08em;">
                                            {{ $dev->pairing_key }}
                                        </span>
                                        <button type="button" onclick="copyPk(this, '{{ $dev->pairing_key }}')"
                                            style="background:none; border:none; cursor:pointer; color:#6b7280; font-size:.72rem; padding:0;"
                                            title="Copy">
                                            Copy
                                        </button>
                                    </span>
                                </td>
                                <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">
                                    {{ $dev->model ?? '—' }}</td>
                                <td
                                    style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef; font-family:monospace; font-size:.8rem;">
                                    {{ $dev->firmware_version ?? '—' }}</td>
                                <td
                                    style="padding:11px 16px; color:#64748b; border-bottom:1px solid #f5eeef; font-size:.8rem;">
                                    {{ $dev->created_at->format('M d, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Rider table --}}
    <h6 class="fw-bold mb-2" style="color:#1e293b; font-size:.88rem; text-transform:uppercase; letter-spacing:.05em;">
        Rider — Device Registry
    </h6>
    <div class="dm-chart-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.83rem;">
                <thead>
                    <tr>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Device Code</th>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Model</th>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Firmware</th>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Battery</th>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Full Name</th>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Age</th>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Contact Number</th>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Status</th>
                        {{-- Cross-referenced against incidents.device_id — a
                             device racking up an unusual number (especially
                             false_alarm-flagged ones) is a hardware/
                             calibration signal worth flagging. --}}
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Incidents</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($riders as $rider)
                        <tr>
                            <td style="padding:11px 16px; color:#334155; border-bottom:1px solid #f5eeef;">
                                @if ($rider->device)
                                    <span
                                        style="font-family:monospace; background:#f1f5f9; padding:2px 8px; border-radius:5px; font-size:.82rem;">
                                        {{ $rider->device->device_code }}
                                    </span>
                                @else
                                    <span style="color:#64748b;">No device</span>
                                @endif
                            </td>
                            <td style="padding:11px 16px; color:#64748b; border-bottom:1px solid #f5eeef;">
                                {{ $rider->device?->model ?? '—' }}</td>
                            <td
                                style="padding:11px 16px; color:#64748b; border-bottom:1px solid #f5eeef; font-family:monospace; font-size:.8rem;">
                                {{ $rider->device?->firmware_version ?? '—' }}</td>
                            <td style="padding:11px 16px; border-bottom:1px solid #f5eeef;">
                                @if ($rider->device?->battery_level !== null)
                                    @php
                                        $battery = $rider->device->battery_level;
                                        $batteryColor = $battery < 20 ? '#991b1b' : ($battery < 50 ? '#92400e' : '#334155');
                                    @endphp
                                    <span style="color:{{ $batteryColor }}; font-weight:{{ $battery < 20 ? '700' : '400' }};">{{ $battery }}%</span>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td
                                style="padding:11px 16px; color:#1e293b; font-weight:500; border-bottom:1px solid #f5eeef;">
                                {{ $rider->full_name }}</td>
                            <td style="padding:11px 16px; color:#64748b; border-bottom:1px solid #f5eeef;">
                                {{ $rider->date_of_birth ? $rider->date_of_birth->age : 'N/A' }}</td>
                            <td style="padding:11px 16px; color:#64748b; border-bottom:1px solid #f5eeef;">
                                {{ $rider->phone_number ?? 'N/A' }}</td>
                            <td style="padding:11px 16px; border-bottom:1px solid #f5eeef;">
                                @if ($rider->device && $rider->device->is_active)
                                    <span
                                        style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; background:#d1fae5; color:#065f46;">Active</span>
                                @elseif($rider->device)
                                    <span
                                        style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; background:#fee2e2; color:#991b1b;">Inactive</span>
                                @else
                                    <span
                                        style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; background:#f1f5f9; color:#64748b;">Unpaired</span>
                                @endif
                            </td>
                            <td style="padding:11px 16px; border-bottom:1px solid #f5eeef;">
                                @if ($rider->device)
                                    <span style="color:#64748b; font-weight:{{ $rider->device->incidents_count > 0 ? '600' : '400' }};">{{ $rider->device->incidents_count }}</span>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No registered riders yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('scripts')
    {{-- Server-side data the external script needs. copyPk() (used by inline
     onclick="" attributes elsewhere on this page) now lives in the external
     file below, along with everything else that was in this block. --}}
    <script>
        window.TocDevicesConfig = {
            chartLabels: @json($chartLabels),
            trendRiders: @json($trendRiders),
            trendDevices: @json($trendDevices),
            trendIncidents: @json($trendIncidents),
            activeDevices: {{ $activeDevices }},
            pairedDevices: {{ $pairedDevices }},
            totalDevices: {{ $totalDevices }},
        };
    </script>
    <script src="{{ asset('js/toc/devices.js') }}?v={{ filemtime(public_path('js/toc/devices.js')) }}"></script>
@endpush
